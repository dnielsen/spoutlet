<?php

namespace Platformd\CEVOBundle\Api;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Platformd\CEVOBundle\Api\ApiException;

/**
 * Handles all API interactions with CEVO
 */
class ApiManager
{
    const API_ENDPOINT = '/api.php';

    const METHOD_AUTH_USER_DETAILS = 'GetAuthenticatedUser';

    private $container;

    private $authManager;

    /**
     * Are we in debug mode?
     *
     * @var bool
     */
    private $debug = false;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * The session ID used to authenticate with CEVO
     *
     * @var string
     */
    private $sessionId;

    /**
     * The current user id
     *
     * @var integer
     */
    private $userId;

    public function __construct(ContainerInterface $container, CEVOAuthManager $authManager, $debug = false)
    {
        $this->container = $container;
        $this->authManager = $authManager;
        $this->debug = $debug;
    }

    /**
     * Returns response from the authenticated user details API calls
     *
     * The raw, deserialized response looks like this:
     *
     * array(3) {
     *      ["api_err_num"]=> int(0)
     *      ["api_err_msg"]=> string(0) ""
     *      ["user"]=> array(9) {
     *          ["user_id"]=> string(7) "1197118"
     *          ["username"]=> string(10) "weaverryan"
     *          ["handle"]=> string(10) "weaverryan"
     *          ["country"]=> string(2) "US"
     *          ["avatar_url"]=> string(52) "http://alienwarearena.com/images/profile-default.png"
     *          ["profile_url"]=> string(41) "http://alienwarearena.com/member/1197118/"
     *          ["email"]=> string(20) "weaverryan@gmail.com"
     *          ["dob"]=> string(10) "1984-06-05"
     *          ["background_link_url"]=> NULL
     *          ["background_image_url"]=> string(26) "/aw-cdn/background-images/"
     *     }
     * }
     *
     * @return array
     */
    public function getAuthenticatedUserDetails()
    {
        $result = $this->makeRequest(self::METHOD_AUTH_USER_DETAILS);

        // sanity check
        if (!isset($result['user'])) {
            throw new ApiException('GetAuthenticatedUser returns JSON, but without a user field');
        }

        return $result['user'];
    }

    /**
     * Allows the auth session id to be set manually
     *
     * This is useful during the authentication process, where the token isn't
     * setup to grab the session automatically yet.
     *
     * @param $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Optional logger dependency
     *
     * @param null|\Symfony\Component\HttpKernel\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the session id to be used for authentication
     *
     * This can be set manually, or we will try to get it from the token
     *
     * @return string
     */
    private function getSessionId()
    {
        if ($this->sessionId === null) {
            $token = $this->getSecurityContext()->getToken();

            if ($token && $token instanceof CEVOToken) {
                $this->sessionId = $token->getSessionId();
            }
        }

        return $this->sessionId;
    }

    /**
     * Returns the CEVO user id to be used for authentication
     *
     * This can be set manually, or we will try to get it from the token
     *
     * @return int
     */
    public function getUserId()
    {
        if ($this->userId === null) {
            $token = $this->getSecurityContext()->getToken();

            if ($token && $token instanceof CEVOToken) {
                $this->userId = $token->getUserId();
            }
        }

        return $this->userId;
    }

    public function getCevoSessionId()
    {
        $sessionId = '';

        if (isset($_COOKIE['aw_session'])) {
            $parts = explode('$', $_COOKIE['aw_session']);
            $sessionId = $parts[1];
        }

        return $sessionId;
    }

    public function getCevoUserId()
    {
        $userId = '';

        if (isset($_COOKIE['aw_session'])) {
            $parts = explode('$', $_COOKIE['aw_session']);
            $userId = $parts[0];
        }

        return $userId;
    }

    /**
     * Makes an API request and returns the array response
     *
     * @param $action
     * @param array $params
     * @return mixed
     * @throws \LogicException
     */
    private function makeRequest($action, array $params = array(), $useCevoAuth = false)
    {
        $params['_method'] = $action;
        $params['_user_id'] = $useCevoAuth ? $this->getCevoUserId() : $this->getUserId();

        $url = $this->authManager->generateCevoUrl(self::API_ENDPOINT, null, false);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $phpSess = sprintf('PHPSESSID=%s;', $useCevoAuth ? $this->getCevoSessionId() : $this->getSessionId());
        curl_setopt($ch, CURLOPT_COOKIE, $phpSess);

        $output = curl_exec($ch);

        // check for errors
        $info = curl_getinfo($ch);
        if (!$output || $info['http_code'] != 200) {
            $this->logError(sprintf('Error making CURL request to CEVO at URL: ' . $action));

            throw new ApiException(sprintf(
                'Error with CEVO API. Status code: %s. Message: %s. URL: %s',
                $info['http_code'],
                curl_error($ch),
                $url
            ));
        }

        curl_close($ch);

        $jsonArr = json_decode($output, true);

        if ($jsonArr === false) {
            throw new ApiException('Problem with CEVO API Response. Content: ' . $output);
        }

        if (isset($jsonArr['error']) && $jsonArr['error']) {
            throw new ApiException('API error. Valid response, but with error: ' . $jsonArr['error']);
        }

        if (isset($jsonArr['api_err_msg']) && $jsonArr['api_err_msg']) {
            throw new ApiException('API error. Valid response, but with error: ' . $jsonArr['api_err_msg']);
        }

        return $jsonArr;
    }

    /**
     * creategroup          | +3 | Create a new group
     * submitgroupvideo     | +2 | Submit an approved video to your group
     * submitgroupphoto     | +2 | Submit an approved photo to your group
     * groupnewscomment     | +1 | Comment on a group news post (currently there is no way to comment on a group news post)
     * groupcommentreply    | +1 | Reply to a group comment thread (see above)
     * joingroup            | +1 | Join a group
     * groupnuke            | -5 | Group nuked
     * photofeature         | +10| Photo is Featured
     * photosubmit          | +3 | Submit an approved photo to the gallery
     * photocomment         | +1 | Comment on a photo
     * nukephotocomment     | -5 | Comment removed
     * nukephoto            | -5 | Photo removed
     *
     * First param, $award is a string. See above list of allowed awards. Second param is $user_id. If it is not specified
     * it will use the currently logged in user.
     *
     * @param $award string
     * @param @user_id int
     * @return array
     */
    public function GiveUserXp($award, $user_id = null)
    {
        $uid = isset($user_id) ? $user_id : $this->getCevoUserId();

        $response = $this->makeRequest('GiveUserXp', array('user' => $uid, 'award' => $award), true);
        return $response;
    }

    private function logError($message)
    {
        if ($this->logger) {
            $this->logger->err($message);
        }
    }

    /**
     * Returns the securiy context
     *
     * The container was injected to avoid a ciricular reference
     *
     * @return \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private function getSecurityContext()
    {
        return $this->container->get('security.context');
    }
}

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

    public function __construct(ContainerInterface $container, CEVOAuthManager $authManager , $debug = false)
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

    /**
     * Makes an API request and returns the array response
     *
     * @param $action
     * @param array $params
     * @return mixed
     * @throws \LogicException
     */
    private function makeRequest($action, array $params = array())
    {
        $params['_method'] = $action;
        $params['_user_id'] = $this->getUserId();

        $url = $this->authManager->generateCevoUrl(self::API_ENDPOINT, null, false);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        $phpSess = sprintf('PHPSESSID=%s;', $this->getSessionId());
        curl_setopt($ch, CURLOPT_COOKIE, $phpSess);

        $output = curl_exec($ch);

        // check for errors
        $info = curl_getinfo($ch);
        if (!$output || $info['http_code'] != 200) {
            $this->logError(sprintf('Error making CURL request to CEVO at URL: '.$action));

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
            throw new ApiException('Problem with CEVO API Response. Content: '.$output);
        }

        if (isset($jsonArr['error']) && $jsonArr['error']) {
            throw new ApiException('API error. Valid response, but with error: '.$jsonArr['error']);
        }

        if (isset($jsonArr['api_err_msg']) && $jsonArr['api_err_msg']) {
            throw new ApiException('API error. Valid response, but with error: '.$jsonArr['api_err_msg']);
        }

        return $jsonArr;
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
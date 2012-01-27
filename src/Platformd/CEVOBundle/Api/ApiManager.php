<?php

namespace Platformd\CEVOBundle\Api;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Platformd\CEVOBundle\Security\CEVO\CEVOToken;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

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

    public function __construct(ContainerInterface $container, CEVOAuthManager $authManager , $debug = false)
    {
        $this->container = $container;
        $this->authManager = $authManager;
        $this->debug = $debug;
    }

    /**
     * Returns response from the authenticated user details API calls
     *
     * @return array
     */
    public function getAuthenticatedUserDetails()
    {
        return $this->makeRequest(self::METHOD_AUTH_USER_DETAILS);
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

        $url = $this->authManager->generateCevoUrl(self::API_ENDPOINT);

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

        // do some error reporting of we're in debug mode
        if ($this->debug) {
            $info = curl_getinfo($ch);
            if ($output === false || $info['http_code'] != 200) {
                throw new \LogicException(sprintf(
                    'Error with CEVO API. Status code: %s. Message: %s. URL: %s',
                    $info['http_code'],
                    curl_error($ch),
                    $url
                ));
            }
        } else {
            $this->logError(sprintf('Error making CURL request to CEVO at URL: '.$action));
        }

        curl_close($ch);

        $jsonArr = json_decode($output, true);

        if ($jsonArr === false) {
            throw new \LogicException('Problem with CEVO API Response. Content: '.$output);
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
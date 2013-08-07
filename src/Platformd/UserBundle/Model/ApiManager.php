<?php

namespace Platformd\UserBundle\Model;

use Platformd\UserBundle\Entity\User;
use Platformd\UserBundle\Exception\ApiRequestException;

use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

class ApiManager
{
    const LOG_MESSAGE_PREFIX = '[ApiManager] %s';

    private $apiBaseUrl;
    private $accessKey;
    private $secretKey;
    private $logger;
    private $translator;

    public function __construct($apiBaseUrl, $accessKey, $secretKey, $logger, $translator) {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->accessKey  = $accessKey;
        $this->secretKey  = $secretKey;
        $this->logger     = $logger;
        $this->translator = $translator;
    }

    /* TODO
        Hook in updatePassword
        Get session details back from authenticate and set cookie
        When checking session expiry in listener, extend browser cookie
        Varnish allow awa_session_key cookie
    */

    private function logInfo($message) {
        $this->logger->info(sprintf(self::LOG_MESSAGE_PREFIX, $message));
    }

    private function getSignedUrl($path, $getParameters=array())
    {
        ksort($getParameters);

        $unsignedUrl = rtrim($this->apiBaseUrl, '/') . '/' . trim($path, '/');
        $query       = http_build_query($getParameters);

        if ($query) {
            $unsignedUrl .= '?' . $query;
        }

        $unsignedUrl = $unsignedUrl . ( $query ? '&' : '?' ) . 'access_key=' . $this->accessKey;
        $unsignedUrl = strtolower($unsignedUrl);
        $signature   = '&sig='.hash_hmac('sha1', $unsignedUrl, $this->secretKey);

        $this->logInfo('Unsigned URL generated - [ '. $unsignedUrl .' ]');

        return $unsignedUrl . $signature;
    }

    public function getSessionInfo($uuid)
    {
        $path   = 'sessions/'.$uuid;
        $result = $this->makeRequest($path, 'GET');
        return $result;
    }

    public function deleteSession($uuid)
    {
        $path   = 'sessions/'.$uuid;
        $result = $this->makeRequest($path, 'DELETE');
        return $result ? $result['metaData']['status'] == 200 : false;
    }

    // Password is the plaintext password presented at login
    public function updatePassword($user, $password)
    {
        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'updatePassword',
            'data'     => array(
                'password' => $password,
            ),
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['status'] == 200 : false;
    }

    public function authenticate($user, $presentedPassword, $returnSession=true)
    {
        if (!$user instanceof User) {
            return false;
        }

        $path           = 'sessions';
        $getParameters  = $returnSession ? array() : array('authenticateonly' => 'true');
        $postParameters = array(
            'action' => 'authenticate',
            'data'   => array(
                'usernameOrEmail' => $user->getEmail(),
                'password'        => $presentedPassword,
            ),
        );

        $result = $this->makeRequest($path, 'POST', array('get' => $getParameters, 'post' => $postParameters));

        if ($result) {

            if ($result['metaData']['status'] == 404) {
                $banned = $result['metaData']['errorCode'] == 40451;
                $suspended = $result['metaData']['errorCode'] == 40452;

                if ($banned) {
                    throw new CredentialsExpiredException(
                        sprintf(
                            $this->translator->trans('fos_user.account_banned', array(), 'validators'),
                            'infinity'
                        )
                    );
                }

                if ($suspended) {
                    $errorParts = explode('`', $result['metaData']['errorMessage']);
                    $expiry = $errorParts[1];
                    $expiryDt = new \DateTime($expiry);

                    throw new CredentialsExpiredException(
                        sprintf(
                            $this->translator->trans('fos_user.account_banned', array(), 'validators'),
                            $expiryDt->format($this->translator->trans('date_format'))
                        )
                    );
                }
            }

            if ($returnSession) {
                return $result['metaData']['status'] == 200 ? $result['data']['uuid'] : false;
            } else {
                return $result['metaData']['status'] == 200;
            }
        }

        return false;
    }

    public function getUserByUsernameOrEmail($usernameOrEmail)
    {
        $path           = 'users';
        $postParameters = array(
            'action' => 'findByUsernameOrEmail',
            'data'   => array(
                'usernameOrEmail' => $usernameOrEmail,
            ),
        );

        $result        = $this->makeRequest($path, 'POST', array('post' => $postParameters));

        return $result ?: null;
    }

    public function updateRemoteUserData($user)
    {
        if ($user instanceof User) {
            $uuid = $user->getUuid();

            $postParameters = array(
                'action' => 'update',
                'user'   => array(
                    'username'        => $user->getUsername(),
                    'email'           => $user->getEmail(),
                    'uuid'            => $uuid,
                    'custom_avatar'   => $user->getAvatar() && $user->getAvatar()->isUsable(),
                    'birth_date'      => $user->getBirthdate() ? $user->getBirthdate()->format('Y-m-d') : null,
                    'first_name'      => $user->getFirstname(),
                    'last_name'       => $user->getLastname(),
                    'country'         => $user->getCountry(),
                    'state'           => $user->getState(),
                    'banned'          => $user->getExpired(),
                    'suspended_until' => $user->getExpiredUntil() ? $user->getExpiredUntil()->format('Y-m-d H:i:s') : null,
                ),
            );
        } elseif (is_array($user)) {
            if (!isset($user['uuid'])) {
                throw new ApiRequestException('updateRemoteUserData - User UUID not set.');
            }

            $uuid = $user['uuid'];
            unset($user['uuid']);

            $postParameters = array(
                'action' => 'update',
                'user' => $user,
            );
        } else {
            throw new ApiRequestException('updateRemoteUserData - Unexpected user type - not User entity or array.');
        }

        $path   = 'users/'.$uuid;
        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));

        return $result ? $result['metaData']['status'] == 200 : false;
    }

    public function createRemoteUser($user, $password)
    {
        $path           = 'users';
        $postParameters = array(
            'action' => 'create',
            'data'   => array(
                'username'            => $user->getUsername(),
                'password'            => $password,
                'email'               => $user->getEmail(),
                'uuid'                => $user->getUuid(),
                'banned'              => false,
                'birth_date'          => $user->getBirthdate() ? $user->getBirthdate()->format('Y-m-d') : null,
                'country'             => $user->getCountry(),
                'created'             => $user->getCreated()->format('Y-m-d H:i:s'),
                'creation_ip_address' => $user->getIpAddress(),
                'custom_avatar'       => false,
                'first_name'          => $user->getFirstName(),
                'last_name'           => $user->getLastName(),
                'last_updated'        => $user->getUpdated()->format('Y-m-d H:i:s'),
                'state'               => $user->getState(),
                'banned'              => $user->getExpired(),
                'suspended_until'     => $user->getExpiredUntil() ? $user->getExpiredUntil()->format('Y-m-d H:i:s') : null,
            ),
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['status'] == 200 : false;
    }

    public function banUser($user)
    {
        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'ban',
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        $success = $result ? $result['metaData']['status'] == 200 : false;

        if (!$success) {
            throw new ApiRequestException();
        }

        return $success;
    }

    public function unbanUser($user)
    {
        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'unban',
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        $success = $result ? $result['metaData']['status'] == 200 : false;

        if (!$success) {
            throw new ApiRequestException();
        }

        return $success;
    }

    public function getUserList($offset=0, $limit=100, $sortMethod='created', $since=null)
    {
        $getParameters = array(
            'limit'   => $limit,
            'offset'  => $offset,
            'orderby' => 'created',
        );

        if ($since) {
            $getParameters['since'] = $since->format('Y-m-d');
        }

        $path   = 'users';
        $result = $this->makeRequest($path. 'GET', array('get' => $getParameters));

        return $result ?: array();
    }

    private function makeRequest($relativeUrl, $method='GET', $parameters = array())
    {
        $getParameters  = isset($parameters['get']) ? $parameters['get'] : array();
        $postParameters = isset($parameters['post']) ? $parameters['post'] : array();

        $url = $this->getSignedUrl($relativeUrl, $getParameters);

        $curl2 = curl_init();

        if (strtolower($method) == "post")
        {
            if (is_array($postParameters)) {
                $parameters = json_encode($postParameters);

                curl_setopt($curl2, CURLOPT_POST, true);
                curl_setopt($curl2, CURLOPT_POSTFIELDS, $parameters);

                curl_setopt($curl2, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($parameters))
                );
            }
        }

        if (strtolower($method) != "post" && strtolower($method) != "get") {
            curl_setopt($curl2, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($curl2, CURLOPT_URL, $url);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl2, CURLOPT_CAINFO, __DIR__.'/../../../../external_data/ssl/RapidSSL.pem');

        $result = curl_exec($curl2);

        return json_decode($result, true);
    }
}

<?php

namespace Platformd\UserBundle\Model;

use Platformd\UserBundle\Entity\User;

class ApiManager
{
    const LOG_MESSAGE_PREFIX = '[ApiManager] %s';

    private $apiBaseUrl;
    private $accessKey;
    private $secretKey;
    private $logger;

    public function __construct($apiBaseUrl, $accessKey, $secretKey, $logger) {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->accessKey  = $accessKey;
        $this->secretKey  = $secretKey;
        $this->logger     = $logger;
    }

    /* TODO

    Hook in updatePassword

    POST: /v1/session
    {
      action: login
      data: {
        usernameOrEmail: asdjklfha
        OR username: asdaf
        OR email: askjdaskld
        password: ajknsdfjkn
      }
    }

    200 also get back the session details
    404 means not valid
    DELETE: /v1/session/9720345-234590-2345-2345-345345
    200 or 404

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
        return $result;
    }

    // Password is the plaintext password presented at login
    public function updatePassword($user, $password)
    {
        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'update',
            'password' => $password,
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['success'] : false;
    }

    public function authenticate($user, $presentedPassword)
    {
        if (!$user instanceof User) {
            return false;
        }
return false;
        $authResult    = $this->dummyAuth($user, $presentedPassword);
        $path          = 'authenticate';
        $getParameters = array(
            'username' => $user->getUsername(),
            'password' => $presentedPassword,
        );

        $result = $this->makeRequest($path, 'GET', array('get' => $getParameters));

        return $result ? $result['metaData']['success'] : false;
    }

    public function getUserByUsername($username)
    {
return null;
        $path          = 'username';
        $getParameters = array('username' => $username);
        $result        = $this->makeRequest($path, 'GET', array('get' => $getParameters));

        return $result ?: null;
    }

    public function updateRemoteUserData($user)
    {
return true;

        if ($user instanceof User) {
            $uuid = $user->getUuid();

            $postParameters = array(
                'action'        => 'update',
                'username'      => $user->getUsername(),
                'email'         => $user->getEmail(),
                'uuid'          => $uuid,
                'custom_avatar' => $user->getAvatar() && $user->getAvatar()->isUsable(),
                'birth_date'    => $user->getBirthdate() ? $user->getBirthdate()->format('Y-m-d') : null,
                'first_name'    => '',
                'last_name'     => '',
                'country'       => $user->getCountry(),
                'state'         => $user->getState(),
                'roles'         => $user->getRoles(),
            );
        } elseif (is_array($user)) {
            if (!isset($user['uuid'])) {
                throw new \Exception('updateRemoteUserData - User UUID not set.');
            }

            $uuid = $user['uuid'];
            unset($user['uuid']);
            unset($user['action']);

            $postParameters = $user;
        } else {
            throw new \Exception('updateRemoteUserData - Unexpected user type - not User entity or array.');
        }

        $path           = 'users/'.$uuid;
        $postParameters = array_merge($postParameters, array('action'   => 'update'));

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));

        return $result ? $result['metaData']['success'] : false;
    }

    public function createRemoteUser($user)
    {
return false;

        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'              => 'create',
            'username'            => $user->getUsername(),
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
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['success'] : false;
    }

    public function banUser($user)
    {
return false;

        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'ban',
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['status'] == 200 : false;
    }

    public function unbanUser($user)
    {
return false;

        $path           = 'users/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'unban',
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
        return $result ? $result['metaData']['status'] == 200 : false;
    }

    public function getUserList($offset=0, $limit=100, $sortMethod='created', $since=null)
    {
return array();

        $getParameters = array(
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => 'created',
        );

        if ($since) {
            $getParameters['since'] = $since->format('Y-m-d');
        }

        $path = 'users';

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
                curl_setopt($curl2, CURLOPT_POSTFIELDS, $postParameters);
            }
        }

        curl_setopt($curl2, CURLOPT_URL, $url);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl2);

        return json_decode($result, true);
    }

    private function dummyAuth($presentedPassword)
    {
        $result = array('metaData'=>array(
            'status'  => 200,
            'success' => false,
        ));

        if ($presentedPassword == 'correctpassword') {
            $result['metaData']['success'] = true;
        }

        return $result;
    }

    private function dummyUpdateUserData()
    {
        $result = array('metaData'=>array(
            'status'  => 200,
            'success' => true,
        ));

        return $result;
    }

    private function dummyGetUser($username)
    {
        return array(
            'metaData' => array(
                'status'  => 200,
                'success' => true,
            ),
            'user' => array(
                'username'    => $username,
                'email'       => 'example@email.com',
                'uuid'        => str_replace("\n", '', `uuidgen -r`),
                'created'     => new \DateTime(),
                'lastUpdated' => new \DateTime(),
            )
        );
    }
}

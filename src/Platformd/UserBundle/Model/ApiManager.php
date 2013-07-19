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

    private function logInfo($message) {
        $this->logger->info(sprintf(self::LOG_MESSAGE_PREFIX, $message));
    }

    private function getSignedUrl($path, $getParameters=array())
    {
        ksort($getParameters);

        $queryString = http_build_query($getParameters) . '&accesskey=' . $this->accessKey;

        $unsignedUrl = strtolower(rtrim($this->apiBaseUrl, '/') . '/' . trim($path, '/') . '?' . $queryString);
        $signature   = '&sig='.hash_hmac('sha1', $unsignedUrl, $this->secretKey);

        $this->logInfo('Unsigned URL generated - [ '. $unsignedUrl .' ]');

        return $unsignedUrl . $signature;
    }

    public function authenticate($user, $presentedPassword)
    {
        if (!$user instanceof User) {
            return false;
        }

        $authResult    = $this->dummyAuth($user, $presentedPassword);
        $path          = 'authenticate';
        $getParameters = array(
            'username' => $user->getUsername(),
            'password' => $presentedPassword,
        );

        $result = $this->makeRequest($path, 'GET', array('get' => $getParameters));
$result = $this->dummyAuth($presentedPassword);
        return $result ? $result['metaData']['success'] : false;
    }

    public function getUserByUsername($username)
    {
        $path          = 'username';
        $getParameters = array('username' => $username);
        $result        = $this->makeRequest($path, 'GET', array('get' => $getParameters));
$result = $this->dummyGetUser($username);
        return $result ?: null;
    }

    public function updateRemoteUserData($user)
    {
        $path           = 'user/'.$user->getUuid();
        $postParameters = array(
            'action'   => 'update',
            'username' => $user->getUsername(),
            'email'    => $user->getEmail(),
            'uuid'     => $user->getUuid(),
        );

        $result = $this->makeRequest($path, 'POST', array('post' => $postParameters));
$result = $this->dummyUpdateUserData();
        return $result ? $result['metaData']['success'] : false;
    }

    public function getUserList($offset=0, $limit=100, $sortMethod='created', $since=null)
    {
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

return;
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

    private function dummyGetUserList()
    {
        $json = '
            {
                "metaData": {
                    "status":       200,
                    "generatedAt":  "2013-05-15T13:59:59Z",
                    "limit":        50,
                    "offset":       0,
                    "orderBy":      "created",
                    "since"         "2001-01-01T00:00:00Z"
                },
                "items": {
                    "item": {
                        "href":         "https://api.alienwarearena.com/v1/user/ae9bc14d-28b0-4547-9dae-090fc11da883",
                        "uuid":         "ae9bc14d-28b0-4547-9dae-090fc11da883",
                        "username":     "tangee",
                        "email":        "example@email.com",
                        "created":      "2013-07-01T20:06:46Z",
                        "lastUpdated":  "2013-07-01T20:06:46Z",
                        "banned":       false,
                        "session":      "66c206f6-33d2-49c8-9618-c54d7c01939a"
                    },
                    "item": {
                        "href":         "https://api.alienwarearena.com/v1/user/cc6f60be-7595-4174-ace7-d00937fe96c1",
                        "uuid":         "cc6f60be-7595-4174-ace7-d00937fe96c1",
                        "username":     "user",
                        "email":        "user@user.com",
                        "created":      "2013-07-01T15:40:01Z",
                        "lastUpdated":  "2013-07-01T15:40:01Z",
                        "banned":       true,
                        "session":      null
                    },
                    "item": {
                        "href":         "https://api.alienwarearena.com/v1/user/b5dfeb60-b2e3-4388-b82a-ec8f2bcf3097",
                        "uuid":         "b5dfeb60-b2e3-4388-b82a-ec8f2bcf3097",
                        "username":     "organizer",
                        "email":        "organizer@organizer.com",
                        "created":      "2013-07-01T15:40:01Z",
                        "lastUpdated":  "2013-07-01T15:40:01Z",
                        "banned":       true,
                        "session":      null
                    },
                    "item": {
                        "href":         "https://api.alienwarearena.com/v1/user/20149912-8272-4236-98fb-7a343abf1e33",
                        "uuid":         "20149912-8272-4236-98fb-7a343abf1e33",
                        "username":     "admin",
                        "email":        "admin@admin.com",
                        "created":      "2013-07-01T15:40:01Z",
                        "lastUpdated":  "2013-07-01T15:40:01Z",
                        "banned":       true,
                        "session":      null
                    }
                }
            }
        ';

        return json_decode($json, true);
    }
}

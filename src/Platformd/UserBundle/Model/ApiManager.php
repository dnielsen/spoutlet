<?php

namespace Platformd\UserBundle\Model;

use Platformd\UserBundle\Entity\User;

class ApiManager
{
    private $apiBaseUrl;

    public function __construct($apiBaseUrl) {
        $this->apiBaseUrl = $apiBaseUrl;
    }

    private function dummyAuth($user, $presentedPassword)
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

    private function dummyGetUser($username)
    {
        return array(
            'metaData' => array(
                'status'  => 200,
                'success' => false,
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
        $json = '{
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
              "href":         "https://api.alienwarearena.com/v1/users/ae9bc14d-28b0-4547-9dae-090fc11da883",
              "uuid":         "ae9bc14d-28b0-4547-9dae-090fc11da883",
              "username":     "tangee",
              "email":        "example@email.com",
              "created":      "2013-07-01T20:06:46Z",
              "lastUpdated":  "2013-07-01T20:06:46Z",
              "banned":       false,
              "session":      "66c206f6-33d2-49c8-9618-c54d7c01939a"
            },
            "item": {
              "href":         "https://api.alienwarearena.com/v1/users/cc6f60be-7595-4174-ace7-d00937fe96c1",
              "uuid":         "cc6f60be-7595-4174-ace7-d00937fe96c1",
              "username":     "user",
              "email":        "user@user.com",
              "created":      "2013-07-01T15:40:01Z",
              "lastUpdated":  "2013-07-01T15:40:01Z",
              "banned":       true,
              "session":      null
            },
            "item": {
              "href":         "https://api.alienwarearena.com/v1/users/b5dfeb60-b2e3-4388-b82a-ec8f2bcf3097",
              "uuid":         "b5dfeb60-b2e3-4388-b82a-ec8f2bcf3097",
              "username":     "organizer",
              "email":        "organizer@organizer.com",
              "created":      "2013-07-01T15:40:01Z",
              "lastUpdated":  "2013-07-01T15:40:01Z",
              "banned":       true,
              "session":      null
            },
            "item": {
              "href":         "https://api.alienwarearena.com/v1/users/20149912-8272-4236-98fb-7a343abf1e33",
              "uuid":         "20149912-8272-4236-98fb-7a343abf1e33",
              "username":     "admin",
              "email":        "admin@admin.com",
              "created":      "2013-07-01T15:40:01Z",
              "lastUpdated":  "2013-07-01T15:40:01Z",
              "banned":       true,
              "session":      null
            }
          }
        }';

        return json_decode($json, true);
    }

    public function authenticate($user, $presentedPassword)
    {
        if (!$user instanceof User) {
            return false;
        }

        $authResult = $this->dummyAuth($user, $presentedPassword);
        return $authResult['metaData']['success'];

        $url = sprintf('authenticate?username=%s&hash=%s', $user->getUsername(), $presentedPassword);

        $result = $this->call($url);

        return $result['metaData']['success'];
    }

    public function getUserByUsername($username)
    {
        return $this->dummyGetUser($username);

        $url = sprintf('username?username=%s', $username);

        $result = $this->call($url);

        return $result;
    }

    public function updateRemoteUserData($user)
    {
        $url        = 'users/'.$user->getUuid();
        $parameters = array(
            'action' => 'update',
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'uuid' => $user->getUuid(),
        );

        return $this->call($url, 'POST', $parameters);
    }

    public function getUserList($offset=0, $limit=100, $sortMethod='created', $since=null)
    {
        return $this->getDummyUserList();
        $sinceQuery = $since ? '&since='.$since->format('Y-m-d') : '';

        $url = 'users?limit='.$limit.'&offset='.$offset.'&orderby=created'.$sinceQuery;
        return $this->call($url);
    }

    private function call($relativeUrl, $method='GET', $parameters = null)
    {
        $url = rtrim($this->apiBaseUrl, '/').'/'.$relativeUrl;

        $curl2 = curl_init();

        if (strtolower($method) == "post")
        {
            if (is_array($parameters)) {
                $parameters = json_encode($parameters);
            }

            if ($this->isJson($parameters)) {

                curl_setopt($curl2, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($parameters))
                );
            }

            curl_setopt($curl2, CURLOPT_POST, true);
            curl_setopt($curl2, CURLOPT_POSTFIELDS, $parameters);
        }

        curl_setopt($curl2, CURLOPT_URL, $url);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl2);

        return json_decode($result, true);
    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

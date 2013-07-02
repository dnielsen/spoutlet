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

    public function authenticate($user, $presentedPassword)
    {
        if (!$user instanceof User) {
            return false;
        }

        $authResult = $this->dummyAuth($user, $presentedPassword);
        return $authResult['metaData']['success'];

        $url = sprintf('authenticate?username=%s&hash=%s', $user->getUsername(), $presentedPassword);

        $result = $this->call($url, 'GET');

        return $result;
    }

    public function getUserByUsername($username)
    {
        return $this->dummyGetUser($username);

        $url = sprintf('username?username=%s', $username);

        $result = $this->call($url, 'GET');

        return $result;
    }

    public function updateRemoteUserData($user)
    {
        $url = 'users/'.$user->getUuid();
        $parameters = array(
            'action' => 'update',
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'uuid' => $user->getUuid(),
        );

        return $this->call($url, 'POST', $parameters);
    }

    private function call($relativeUrl, $method, $parameters = null)
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

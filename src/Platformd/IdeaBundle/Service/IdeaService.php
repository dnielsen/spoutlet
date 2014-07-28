<?php

namespace Platformd\IdeaBundle\Service;

use \Exception;
use Platformd\IdeaBundle\Entity\Tag;
use Doctrine\ORM\EntityManager as EntityManager;

class IdeaService
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    /**
     * Takes the user submitted string of tags, parses it, checks
     * against existing tags and returns an array of new tag objects
     */
    public function processTags($allTagsString)
    {
        $newTags = array();

        $allTagsString = trim(strtolower($allTagsString));

        if (empty($allTagsString)) {
            return $newTags;
        }

        $tagStrings = preg_split("/[\s,]+/", $allTagsString);
        $allTagNames = $this->getAllTagNames();

        foreach ($tagStrings as $tagString) {
            $tagString = trim($tagString);
            if (empty($tagString)) {
                continue;
            }

            if (!in_array($tagString, $allTagNames)) {
                $newTag = new Tag($tagString);
                if (!in_array($newTag, $newTags)) {
                    $newTags[] = $newTag;
                    $this->em->persist($newTag);
                }
            } else {
                $newTags[] = $this->em->getRepository('IdeaBundle:Tag')->find($tagString);
            }
        }
        $this->em->flush();
        return $newTags;
    }

    public function getAllTagNames()
    {
        $tagNames = array();
        $allTags = $this->em->getRepository('IdeaBundle:Tag')->findAll();
        foreach ($allTags as $tag) {
            $tagNames[] = $tag->getTagName();
        }
        return $tagNames;
    }

    public function getContainer($scope, $containerId)
    {
        if ($scope == 'group') {
            $repo = 'GroupBundle:Group';
        } elseif ($scope == 'event') {
            $repo = 'EventBundle:GroupEvent';
        } elseif ($scope == 'global_event') {
            $repo = 'EventBundle:GlobalEvent';
        } else {
            return null;
        }

        return $this->em->getRepository($repo)->find($containerId);
    }

    public function getParentByEntrySet($entrySet)
    {
        $parentRegistration = $entrySet->getEntrySetRegistration();
        $esRegRepo = $this->em->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($parentRegistration);
    }

    public function getEventbriteEvent($ebEventId) {
        $apiKey = 'KDD4LXVSOJDICJEGPJ';
        $url = 'https://www.eventbrite.com/json/event_get?app_key=' . $apiKey . '&id=' . $ebEventId;

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);     //We want the result to be saved into variable, not printed out
        $response = curl_exec($handle);
        $json_resp = json_decode($response, true);
        
        // Check if any error occurred
        if(!curl_errno($handle))
        {
            $info = curl_getinfo($handle);
            if($info['http_code'] >= 300 || $info['http_code'] < 200) {
                $error_msg = "Error: " . $info['http_code'];
                if( array_key_exists('error', $json_resp)) {
                    $error_msg += " " . $json_resp['error']['error_type'] . ": " . $json_resp['error']['error_message'];
                }
                throw new Exception($error_msg);
            }
                
        }

        curl_close($handle);

        if($json_resp == null) {
            return null;
        } else if(array_key_exists('event', $json_resp)) {
            return $json_resp['event'];
        } 

        return null;
    }

    public function getMeetupEvent($muEventId) {
        $apiKey = "7ae43171676a5440625b593573722e";
        $url = "https://api.meetup.com/2/event/" . $muEventId . "?key=" . $apiKey;

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);     //We want the result to be saved into variable, not printed out
        $response = curl_exec($handle);
        $json_resp = json_decode($response, true);
        
        // Check if any error occurred
        if(!curl_errno($handle))
        {
            $info = curl_getinfo($handle);
            if($info['http_code'] >= 300 || $info['http_code'] < 200) {
                $error_msg = "Error: " . $info['http_code'];
                if( array_key_exists('error', $json_resp)) {
                    $error_msg += " " . $json_resp['error']['error_type'] . ": " . $json_resp['error']['error_message'];
                }
                throw new Exception($error_msg);
            }
                
        }

        curl_close($handle);

        return $json_resp;
    }
}

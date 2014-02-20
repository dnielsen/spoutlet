<?php

namespace Platformd\IdeaBundle\Service;

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

        if(empty($allTagsString)){
            return $newTags;
        }

        $tagStrings = preg_split("/[\s,]+/", $allTagsString);
        $allTagNames = $this->getAllTagNames();

        foreach ($tagStrings as $tagString)
        {
            $tagString = trim($tagString);
            if (empty($tagString)){
                continue;
            }

            if (!in_array($tagString, $allTagNames))
            {
                $newTag = new Tag($tagString);
                if(!in_array($newTag, $newTags))
                {
                    $newTags[] = $newTag;
                    $this->em->persist($newTag);
                }
            }
            else
            {
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
        foreach ($allTags as $tag)
        {
            $tagNames[] = $tag->getTagName();
        }
        return $tagNames;
    }

}

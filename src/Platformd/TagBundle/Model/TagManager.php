<?php

namespace Platformd\TagBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\Commons\Collections\Collection;
use Platformd\TagBundle\Entity\Tag;
use Platformd\TagBundle\Entity\Tagging;
use Platformd\TagBundle\Model\TaggableInterface;

class TagManager
{
    protected $em;
    protected $tagClass;
    protected $taggingClass;

    private static $resourceClassMap = array(
        'platformd_news'             => 'NewsBundle:News',
        'platformd_group_discussion' => 'GroupBundle:GroupDiscussion',
        'platformd_group'            => 'GroupBundle:Group',
        'platformd_group_news'       => 'GroupBundle:GroupNews',
        'platformd_giveaway'         => 'GiveawayBundle:Giveaway',
        'platformd_deal'             => 'GiveawayBundle:Deal',
        'platformd_global_event'     => 'EventBundle:GlobalEvent',
        'platformd_group_event'      => 'EventBundle:GroupEvent',
        'platformd_contest'          => 'SpoutletBundle:Contest',
        'platformd_gallery_media'    => 'SpoutletBundle:GalleryMedia',
        'platformd_sweepstakes'      => 'SweepstakesBundle:Sweepstakes',
        'platformd_youtube_video'    => 'VideoBundle:YoutubeVideo',
    );

    public function __construct(EntityManager $em, $tagClass = null, $taggingClass = null)
    {
        $this->em           = $em;

        $this->tagClass     = $tagClass ?: 'Platformd\TagBundle\Entity\Tag';
        $this->taggingClass = $taggingClass ?: 'Platformd\TagBundle\Entity\Tagging';
    }

    public function getClassFromResource($resource)
    {
        return (isset(self::$resourceClassMap[$resource])) ? self::$resourceClassMap[$resource] : null;
    }

    /**
     * Adds a tag on the given taggable resource
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource
     */
    public function addTag(Tag $tag, TaggableInterface $resource)
    {
        $resource->getTags()->add($tag);
    }

    /**
     * Adds multiple tags on the given taggable resource
     *
     * @param Tag[]     $tags       Array of Tag objects
     * @param Taggable  $resource   Taggable resource
     */
    public function addTags(array $tags, TaggableInterface $resource)
    {
        foreach ($tags as $tag) {
            if ($tag instanceof Tag) {
                $this->addTag($tag, $resource);
            }
        }
    }

    /**
     * Removes an existant tag on the given taggable resource
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource
     * @return Boolean
     */
    public function removeTag(Tag $tag, TaggableInterface $resource)
    {
        $tag->setTimesUsed($tag->getTimesUsed() - 1);
        return $resource->getTags()->removeElement($tag);
    }

    /**
     * Replaces all current tags on the given taggable resource
     *
     * @param Tag[]     $tags       Array of Tag objects
     * @param Taggable  $resource   Taggable resource
     */
    public function replaceTags(array $tags, TaggableInterface $resource)
    {
        $resource->getTags()->clear();
        $this->addTags($tags, $resource);
    }

    /**
     * Loads or creates a tag from tag name
     *
     * @param array  $name  Tag name
     * @return Tag
     */
    public function loadOrCreateTag($name)
    {
        $tags = $this->loadOrCreateTags(array($name));
        return $tags[0];
    }

    /**
     * Loads or creates multiples tags from a list of tag names
     *
     * @param array  $names   Array of tag names
     * @return Tag[]
     */
    public function loadOrCreateTags(array $names, $author=null)
    {
        if (empty($names)) {
            return array();
        }

        $names = array_unique($names);

        $builder = $this->em->createQueryBuilder();

        $tags = $builder
            ->select('t')
            ->from($this->tagClass, 't')

            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;

        $loadedNames = array();
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tag = $this->createTag($name);
                $this->em->persist($tag);

                $tags[] = $tag;
            }

            $this->em->flush();
        }

        return $tags;
    }

    /**
     * Saves tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function saveTagging(TaggableInterface $resource)
    {
        $oldTags = $this->getTagging($resource);
        $newTags = $resource->getTags();
        $tagsToAdd = $newTags;

        if ($oldTags !== null and is_array($oldTags) and !empty($oldTags)) {
            $tagsToRemove = array();

            foreach ($oldTags as $oldTag) {
                if ($newTags->exists(function ($index, $newTag) use ($oldTag) {
                    return $newTag->getName() == $oldTag->getName();
                })) {
                    $tagsToAdd->removeElement($oldTag);
                } else {
                    $tagsToRemove[] = $oldTag->getId();
                }
            }

            if (sizeof($tagsToRemove)) {
                $builder = $this->em->createQueryBuilder();
                $builder
                    ->delete($this->taggingClass, 't')
                    ->where('t.tag_id')
                    ->where($builder->expr()->in('t.tag', $tagsToRemove))
                    ->andWhere('t.resourceType = :resourceType')
                    ->setParameter('resourceType', $resource->getTaggableType())
                    ->andWhere('t.resourceId = :resourceId')
                    ->setParameter('resourceId', $resource->getTaggableId())
                    ->getQuery()
                    ->getResult()
                ;
            }
        }

        foreach ($tagsToAdd as $tag) {
            $this->em->persist($tag);
            $this->em->persist($this->createTagging($tag, $resource));
        }

        if (count($tagsToAdd)) {
            $this->em->flush();
        }
    }

    /**
     * Loads all tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function loadTagging(TaggableInterface $resource)
    {
        $tags = $this->getTagging($resource);
        $this->replaceTags($tags, $resource);
    }

    /**
     * Gets all tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    protected function getTagging(TaggableInterface $resource, $status=Tag::STATUS_ACTIVE)
    {
        return $this->em
            ->createQueryBuilder()

            ->select('t')
            ->from($this->tagClass, 't')
            ->where('t.status = :status')
            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceId = :id AND t2.resourceType = :type')
            ->setParameter('id', $resource->getTaggableId())
            ->setParameter('type', $resource->getTaggableType())
            ->setParameter('status', $status)

            // ->orderBy('t.name', 'ASC')

            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Deletes all tagging records for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function deleteTagging(TaggableInterface $resource)
    {
        $taggingList = $this->em->createQueryBuilder()
            ->select('t')
            ->from($this->taggingClass, 't')

            ->where('t.resourceType = :type')
            ->setParameter('type', $resource->getTaggableType())

            ->andWhere('t.resourceId = :id')
            ->setParameter('id', $resource->getTaggableId())

            ->getQuery()
            ->getResult();

        foreach ($taggingList as $tagging) {
            $this->em->remove($tagging);
        }
    }

    /**
     * Splits an string into an array of valid tag names
     *
     * @param string    $names      String of tag names
     * @param string    $separator  Tag name separator
     */
    public function splitTagNames($names, $separator=',')
    {
        $tags = explode($separator, $names);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($value) { return !empty($value); });

        return array_values($tags);
    }

    /**
     * Returns an array of tag names for the given Taggable resource.
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function getTagNames(TaggableInterface $resource)
    {
        $names = array();

        if (sizeof($resource->getTags()) > 0) {
            foreach ($resource->getTags() as $tag) {
                $names[] = $tag->getName();
            }
        }

        return $names;
    }

    /**
     *
     *
     *
     */
    public function getConcatenatedTagNames(TaggableInterface $resource, $separator=', ')
    {
        $names = array();

        if (sizeof($resource->getTags()) > 0) {
            foreach ($resource->getTags() as $tag) {
                $names[] = $tag->getName();
            }
        }

        return implode($separator, $names);
    }

    /**
     * Creates a new Tag object
     *
     * @param string    $name   Tag name
     * @return Tag
     */
    protected function createTag($name)
    {
        return new $this->tagClass($name);
    }

    /**
     * Creates a new Tagging object
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource object
     * @return Tagging
     */
    protected function createTagging(Tag $tag, TaggableInterface $resource)
    {
        return new $this->taggingClass($tag, $resource);
    }

    /**
     * Gets all tags
     *
     * @return Tag[]
     */
    public function getAllTags()
    {
        return $this->em->getRepository('TagBundle:Tag')->findAll();
    }

    /**
     * Gets all tags
     *
     * @return Tag[]
     */
    public function getAllTagsSortByAlpha()
    {
        return $this->em->getRepository('TagBundle:Tag')->getAllTagsSortByAlpha();
    }

    /**
     *
     *
     *
     */
    public function getAllTagsSortByAlphaWithCount()
    {
        return $this->em->getRepository('TagBundle:Tag')->getAllTagsSortByAlphaWithCount();
    }

    /**
     * Gets all tags sorted by last updated
     *
     * @return Tag[]
     */
    public function getAllTagsSortedByLastUpdated()
    {
        return $this->em->getRepository('TagBundle:Tag')->getAllTagsSortedByLastUpdated();
    }

    /**
     * Gets a tag
     *
     * @param integer   $id     id of tag to find
     * @return Tag
     */
    public function findTag($id)
    {
        return $this->em->getRepository('TagBundle:Tag')->find($id);
    }

    /**
     * Saves a tag
     *
     * @param Tag       $tag        Tag object
     */
    public function saveTag(Tag $tag)
    {
        $this->em->persist($tag);
        $this->em->flush();
    }
}

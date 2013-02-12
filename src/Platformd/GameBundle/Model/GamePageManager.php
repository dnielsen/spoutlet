<?php

namespace Platformd\GameBundle\Model;

use Platformd\GameBundle\Entity\GamePage;
use Doctrine\ORM\EntityManager;
use Platformd\GameBundle\Entity\GamePageLocale;
use Knp\MediaBundle\Util\MediaUtil;

class GamePageManager
{
    private $em;
    private $mediaUtil;

    public function __construct(EntityManager $em, MediaUtil $mediaUtil)
    {
        $this->em = $em;
        $this->mediaUtil = $mediaUtil;
    }

    /**
     * Call this to save a GamePage
     *
     * This must be used instead of persisting it directly
     *
     * @param \Platformd\GameBundle\Entity\GamePage $gamePage
     */
    public function saveGamePage(GamePage $gamePage)
    {
        $this->em->persist($gamePage);

        $this->handleMediaFields($gamePage);
        $this->handleMediaGallery($gamePage);

        $this->em->flush();
    }

    public function findAllGamePagesWhereIdNotIn($inArray, $site) {
        return $this->getRepository()->findAllGamePagesWhereIdNotIn($inArray, $site);
    }

    public function findAllForSiteNewestFirst($site)
    {
        return $this->getRepository()->findAllForSiteNewestFirst($site);
    }

    public function findAllByGamePagesByCategory($category, $site, $limit = null)
    {
        $pages = $this->getRepository()->findAllByGamePagesByCategory($category, $site, $limit);

        return $pages;
    }

    /**
     * Returns an array of arrays of active GamePage objects relevant for this user
     *
     * array(
     *      'rpg'       => array($gamePage1, $gamePage3),
     *      'action'    => array($gamePage3, $gamePage4)
     * )
     *
     * @param integer $age
     * @return array
     */
    public function findActiveGamesInCategoriesForAge($age, $site)
    {
        $activeGames = $this->getRepository()->findActiveGamesForAge($age, $site);

        $categorized = array();
        foreach ($activeGames as $activeGame) {
            $category = $activeGame->getGame()->getCategory();
            if (!isset($categorized[$category])) {
                $categorized[$category] = array();
            }

            $categorized[$category][] = $activeGame;
            // temporarily tripled for ease of testing
            // $categorized[$category][] = $activeGame;
            // $categorized[$category][] = $activeGame;
        }

        return $categorized;
    }

    /**
     * @param $age
     * @return \Platformd\GameBundle\Entity\GamePAge
     */
    public function findMostRecentGamePageForAge($age, $site)
    {
        return $this->getRepository()->findMostRecentGameForAge($age, $site);
    }

    /**
     * @return \Platformd\GameBundle\Entity\GamePage[]
     */
    public function findArchives($site)
    {
        return $this->getRepository()->findArchivesForSite($site);
    }

    /**
     * @param string $slug
     * @return \Platformd\GameBundle\Entity\GamePage
     */
    public function findOneBySlug($slug, $site)
    {
        return $this->getRepository()->findOneBySlugForSite($slug, $site);
    }

    /**
     * Properly persists or unsets the media fields
     *
     * @param \Platformd\GameBundle\Entity\GamePage $gamePage
     */
    private function handleMediaFields(GamePage $gamePage)
    {
        $mUtil = $this->mediaUtil;

        if (!$mUtil->persistRelatedMedia($gamePage->getButtonImage1())) {
            $gamePage->setButtonImage1(null);
        }

        if (!$mUtil->persistRelatedMedia($gamePage->getButtonImage2())) {
            $gamePage->setButtonImage2(null);
        }

        if (!$mUtil->persistRelatedMedia($gamePage->getBackgroundImage())) {
            $gamePage->setBackgroundImage(null);
        }
    }

    /**
     * Handles the gallery medias persistence
     *
     * @param \Platformd\GameBundle\Entity\GamePage $gamePage
     */
    private function handleMediaGallery(GamePage $gamePage)
    {
        /** @var $media \Platformd\MediaBundle\Entity\Media */
        foreach ($gamePage->getMediaGalleryMedias() as $media) {
            // either persist it, or remove it from the collection
            if (!$this->mediaUtil->persistRelatedMedia($media)) {
                $gamePage->getMediaGalleryMedias()->removeElement($media);
            }
        }
    }

    /**
     * @return \Platformd\GameBundle\Entity\GamePageRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('GameBundle:GamePage');
    }


}

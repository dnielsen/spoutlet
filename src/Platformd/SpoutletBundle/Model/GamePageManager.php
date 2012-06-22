<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\SpoutletBundle\Entity\GamePage;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\GamePageLocale;
use Symfony\Component\HttpFoundation\Session;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Locale\LocalesRelationshipHelper;

/**
 * Manager for GamePage.
 *
 * In particular, this transforms a "locales" array property to a proper "hasMany" relationship
 *
 * This helps us overcome the fact that "locales" aren't a database object,
 * so creating a classic ManyToMany to it actually involves creating a OneToMany
 * to a new entity. Getting that in a form is hard, unless we can just set
 * a locales array and have a listener handle the relationship.
 */
class GamePageManager
{
    private $em;

    private $session;

    private $mediaUtil;

    private $localesHelper;

    public function __construct(EntityManager $em, Session $session, MediaUtil $mediaUtil, LocalesRelationshipHelper $localesHelper)
    {
        $this->em = $em;
        $this->session = $session;
        $this->mediaUtil = $mediaUtil;
        $this->localesHelper = $localesHelper;
    }

    /**
     * Call this to save a GamePage
     *
     * The originalGalleryMedias is an array of the Media objects that were
     * part of the media *before* any changes were made to this entity.
     *
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
     */
    public function saveGamePage(GamePage $gamePage)
    {
        $this->em->persist($gamePage);

        $this->localesHelper->processLocalesSave($gamePage);
        $this->handleMediaFields($gamePage);
        $this->handleMediaGallery($gamePage);

        $this->em->flush();
    }

    /**
     * Finds all the GamePage objects that are related to the given site.
     *
     * Orders them by newest first
     *
     * @param string $site The site/locale
     * @return \Platformd\SpoutletBundle\Entity\GamePage[]
     */
    public function findAllForSiteNewestFirst($site)
    {
        return $this->getRepository()->findAllForSiteNewestFirst($site);
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
    public function findActiveGamesInCategoriesForAge($age)
    {
        $activeGames = $this->getRepository()->findActiveGamesForAge($age, $this->getDatabaseSiteKey());

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
     * @return \Platformd\SpoutletBundle\Entity\GamePAge
     */
    public function findMostRecentGamePageForAge($age)
    {
        return $this->getRepository()->findMostRecentGameForAge($age, $this->getDatabaseSiteKey());
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\GamePage[]
     */
    public function findArchives()
    {
        return $this->getRepository()->findArchivesForSite($this->getDatabaseSiteKey());
    }

    /**
     * @param string $slug
     * @return \Platformd\SpoutletBundle\Entity\GamePage
     */
    public function findOneBySlug($slug)
    {
        return $this->getRepository()->findOneBySlugForSite($slug, $this->getDatabaseSiteKey());
    }

    /**
     * Properly persists or unsets the media fields
     *
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
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
     * @param \Platformd\SpoutletBundle\Entity\GamePage $gamePage
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
     * @return \Platformd\SpoutletBundle\Entity\GamePageRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('SpoutletBundle:GamePage');
    }

    private function getDatabaseSiteKey()
    {
        return $this->session->getLocale();
    }
}

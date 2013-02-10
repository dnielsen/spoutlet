<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\UserBundle\Entity\User;

class SiteMigrationController extends Controller
{

    private $em;
    private $abstractEventRepo;
    private $dealRepo;
    private $newsRepo;
    private $gamePageRepo;
    private $siteRepo;

    public function migrateAction()
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User || !$user->hasRole('ROLE_SUPER_ADMIN')) {
            echo "Sorry, you are not allowed to access this page.";
            return;
        }

        echo "Migrating locales to sites...<br /><br />";

        $this->em                 = $this->getDoctrine()->getEntityManager();
        $this->abstractEventRepo  = $this->em->getRepository('SpoutletBundle:AbstractEvent');
        $this->dealRepo           = $this->em->getRepository('GiveawayBundle:Deal');
        $this->newsRepo           = $this->em->getRepository('NewsBundle:News');
        $this->gamePageRepo       = $this->em->getRepository('SpoutletBundle:GamePage');
        $this->siteRepo           = $this->em->getRepository('SpoutletBundle:Site');

        echo " - Migrating AbsractEvents...<br />";

        $abstractEvent      = $this->getResults($this->abstractEventRepo);
        $this->migrate($abstractEvent);

        echo " - Migrating Deals...<br />";

        $deals              = $this->getResults($this->dealRepo);
        $this->migrate($deals);

        echo " - Migrating News...<br />";

        $news               = $this->getResults($this->newsRepo);
        $this->migrate($news);

        echo " - Migrating GamePages...<br />";

        $gamePages          = $this->getResults($this->gamePageRepo);
        $this->migrate($gamePages);

        echo "<br />Finished migrations.";

        exit;
    }

    private function getResults($repository)
    {
        return $repository->createQueryBuilder('r')
            ->andWhere('r.sitifiedAt IS NULL')
            ->getQuery()
            ->execute();
    }

    private function migrate($results)
    {
        foreach ($results as $result) {
            if (method_exists($result, 'getLocale')) {

                $site = $this->siteRepo->findOneByDefaultLocale($result->getLocale());
                if ($site) {
                    $result->getSites()->add($site);
                }

            } elseif (method_exists($result, 'getDealLocales')) {

                foreach ($result->getDealLocales() as $dealLocale) {
                    $site = $this->siteRepo->findOneByDefaultLocale($dealLocale->getLocale());
                    if ($site) {
                        $result->getSites()->add($site);
                    }
                }

            } elseif (method_exists($result, 'getGamePageLocales')) {

                foreach ($result->getGamePageLocales() as $gamePageLocale) {
                    $site = $this->siteRepo->findOneByDefaultLocale($gamePageLocale->getLocale());
                    if ($site) {
                        $result->getSites()->add($site);
                    }
                }

            } elseif (method_exists($result, 'getLocales')) {

                foreach ($result->getLocales() as $locale) {
                    $site = $this->siteRepo->findOneByDefaultLocale($locale);
                    if ($site) {
                        $result->getSites()->add($site);
                    }
                }
            }

            $result->setSitifiedAt(new \DateTime('now'));

            $this->em->persist($result);
            $this->em->flush();
        }
    }
}

<?php

namespace Platformd\SpoutletBundle\Tests\Entity;

use Platformd\SpoutletBundle\Test\WebTestCase;
use Platformd\SpoutletBundle\Entity\Deal;
use DateTime;
use DateTimeZone;

class DealRepositoryTest extends WebTestCase
{
    public function testFindAllActiveNonFeatureDealsForSite()
    {
        self::createClient();

        $this->emptyModel('SpoutletBundle:Deal');

        $site = 'en';
        $em = $this->getEntityManager();

        // an unpublished deal (but otherwise would be non-expired)
        $deal1 = $this->createDeal('deal1', false);

        // a published, active deal (no start/end)
        $deal2 = $this->createDeal('deal2', true);

        // a published, active deal (inside the start and end dates)
        $deal3 = $this->createDeal('deal3', true);
        $deal3->setStartsAt(new DateTime('yesterday'));
        $deal3->setEndsAt(new DateTime('tomorrow'));

        // a published, inactive deal (not started yet)
        $deal4 = $this->createDeal('deal4', true);
        $deal4->setStartsAt(new DateTime('tomorrow'));
        $deal4->setEndsAt(new DateTime('next week'));

        // a published, inactive deal (expired)
        $deal5 = $this->createDeal('deal5', true);
        $deal5->setStartsAt(new DateTime('last week'));
        $deal5->setEndsAt(new DateTime('yesterday'));

        // a published, active deal (barely started, almost expired - test for UTC stuff)
        $deal6 = $this->createDeal('deal6', true);
        $starts = new \DateTime('1 hour ago', new DateTimeZone('UTC'));
        $ends = new \DateTime('+1 hour', new DateTimeZone('UTC'));
        $deal6->setStartsAt($starts);
        $deal6->setEndsAt($ends);

        // we can skip the deal mananger here since each deal already went through saveDeal (and we haven't made any other changes important for that call)
        $this->getEntityManager()->flush();

        /** @var $repo \Platformd\SpoutletBundle\Entity\DealRepository */
        $repo = $this->getEntityManager()->getRepository('SpoutletBundle:Deal');

        $actives = $repo->findAllActiveNonFeatureDealsForSite($site, array());
        // there are 3 active deals (deal2, deal3, deal6);
        $this->assertEquals(3, count($actives));
        $this->assertEquals(array($deal2, $deal3, $deal6), $actives);

        $actives = $repo->findAllActiveNonFeatureDealsForSite($site, array($deal3, $deal6));
        $this->assertEquals(1, count($actives));
        $this->assertEquals('deal2', $actives[0]->getName());
    }

    private function createDeal($name, $isPublished)
    {
        $deal = new Deal();
        $deal->setName($name);
        $deal->setLocales(array('en'));
        $deal->setRedemptionInstructionsArray(array('instructions'));

        if ($isPublished) {
            $deal->setStatus(Deal::STATUS_PUBLISHED);
        } else {
            $deal->setStatus(Deal::STATUS_UNPUBLISHED);
        }

        // save, but don't flush
        $this->getDealManager()->saveDeal($deal, false);

        return $deal;
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\DealManager
     */
    private function getDealManager()
    {
        return $this->getContainer()->get('platformd.model.deal_manager');
    }
}
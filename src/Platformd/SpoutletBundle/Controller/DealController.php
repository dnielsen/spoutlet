<?php

namespace Platformd\SpoutletBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Locale\Locale;
use Platformd\SpoutletBundle\Entity\Deal;
use Symfony\Component\Validator\Constraints\NotBlank;

class DealController extends Controller
{
    /**
     * The main deals "list" page
     * @Template
     */
    public function indexAction()
    {
        $featuredDeals = $this->getDealManager()->findFeaturedDeals();
        $mainDeal = empty($featuredDeals) ? null : $featuredDeals[0];
        $allDeals = $this->getDealManager()->findActiveDeals($featuredDeals);
        $expiredDeals = $this->getDealManager()->findExpiredDeals();
        $commentsArr = $this->getCommentManager()
            ->findMostRecentCommentsByThreadPrefixWithObjects(Deal::COMMENT_PREFIX, 5)
        ;

        return array(
            'mainDeal'          => $mainDeal,
            'featuredDeals'     => $featuredDeals,
            'allDeals'          => $allDeals,
            'expiredDeals'      => $expiredDeals,
            'commentsArr'       => $commentsArr,
        );
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug, Request $request)
    {
        $em                     = $this->getDoctrine()->getEntityManager();
        $deal                   = $em->getRepository('SpoutletBundle:Deal')->findOneBySlug($slug);
        $dealCodeRepo           = $em->getRepository('SpoutletBundle:DealCode');
        $countries              = Locale::getDisplayCountries('en');

        $user                   = $this->getUser();
        $userAlreadyRedeemed    = false;
        $dealCode               = '';
        $dealCodeIsUrl          = false;

        if (!$deal) {
            throw $this->createNotFoundException('No deal found in this site for slug '.$slug);
        }

        $dealPools  = $deal->getDealPools();

        # I'm so behind and I put this here as a place holder... I really hope it gets removed before I am forced to commit push...
        # this needs converted into a nice single sql statement repo function - this will not scale but I simply don't have time right now :(
        # [N+1 problem] - chris

        $allowedCountries = array();

        foreach ($dealPools as $pool) {
            foreach ($pool->getAllowedCountries() as $country) {
                $allowedCountries[$country->getCode()] = $country->getName();
            }
        }

        if (count($allowedCountries) > 1) {
            asort($allowedCountries);
        }

        $loggedIn   = $this->get('security.context')->isGranted('ROLE_USER');

        $hasKeys = $dealCodeRepo->getTotalAvailableForDeal($deal);

        if ($loggedIn) {

            $currentlyAssigned = $dealCodeRepo->getUserAssignedCodeForDeal($user, $deal);

            if ($currentlyAssigned) {
                $userAlreadyRedeemed = true;
                $dealCode = $currentlyAssigned->getValue();
                $dealCodeIsUrl = $currentlyAssigned->getPool()->getKeysAreUrls();
            }
        }

        $instructions = $deal->getCleanedRedemptionInstructionsArray();
        return array(
            'deal' => $deal,
            'userAlreadyRedeemed' => $userAlreadyRedeemed,
            'dealCode' => $dealCode,
            'dealCodeIsUrl' => $dealCodeIsUrl,
            'redemptionSteps' => $instructions,
            'allowedCountries' => $allowedCountries,
            'hasKeys' => $hasKeys > 0,
        );
    }

    /**
     * @param string $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function redeemAction($slug, Request $request)
    {

        $this->basicSecurityCheck(array('ROLE_USER'));

        $em             = $this->getDoctrine()->getEntityManager();
        $dealCodeRepo   = $em->getRepository('SpoutletBundle:DealCode');
        $dealPoolRepo   = $em->getRepository('SpoutletBundle:DealPool');
        $deal           = $this->getDealManager()->findOneBySlug($slug);
        $clientIp       = $request->getClientIp(true);
        $user           = $this->getUser();
        $locale         = $this->getLocale();
        $countryRepo    = $em->getRepository('SpoutletBundle:Country');
        $dealShow       = $this->generateUrl('deal_show', array('slug' => $slug));

        if ($dealCodeRepo->doesUserHaveCodeForDeal($user, $deal)) {
            $this->setFlash('error', 'deal_redeem_user_already_redeemed');
            return $this->redirect($dealShow);
        }

        $country = $countryRepo->findOneByCode(strtoupper($user->getCountry()));

        if (!$country) {
            $this->setFlash('error', 'deal_redeem_invalid_country');
            return $this->redirect($dealShow);
        }

        if (!$country) {
            $this->setFlash('error', 'deal_redeem_invalid_country');
            return $this->redirect($dealShow);
        }

        $pools = $dealPoolRepo->getAllPoolsForDealGivenCountry($deal, $country);

        if (!$pools || count($pools) < 1) {
            $this->setFlash('error', 'deal_redeem_no_keys_left');
            return $this->redirect($dealShow);
        }

        $code = null;
        $lastFail = null;

        foreach ($pools as $pool) {

            if (!$dealCodeRepo->canIpHaveMoreKeys($clientIp, $pool)) {
                $lastFail = 'deal_redeem_max_ip_hit';
                continue;
            }

            $code = $dealCodeRepo->getUnassignedKey($pool);

            if (!$code) {
                $lastFail = 'deal_redeem_no_keys_left';
                continue;
            }

            $lastFail = null;
            break;
        }

        if ($lastFail) {
            $this->setFlash('error', $lastFail);
            return $this->redirect($dealShow);
        }

        $code->assign($user, $clientIp, $locale);
        $code->setCountry($country); # in addition to assigning the deal code, we need to set the country (this is one of the differences between a Code and a DealCode)

        $em->flush();

        return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
    }

     /**
     * @return \Platformd\SpoutletBundle\Model\DealManager
     */
    private function getDealManager()
    {
        return $this->get('platformd.model.deal_manager');
    }

    /**
     * @return \Platformd\CommentBundle\Model\CommentManager
     */
    protected function getCommentManager()
    {
        return $this->container->get('fos_comment.manager.comment');
    }
}

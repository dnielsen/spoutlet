<?php

namespace Platformd\SpoutletBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Locale\Locale;
use Platformd\SpoutletBundle\Entity\Deal;

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
    public function showAction($slug)
    {
        $em                     = $this->getDoctrine()->getEntityManager();
        $deal                   = $em->getRepository('SpoutletBundle:Deal')->findOneBySlug($slug);
        $dealCodeRepo           = $em->getRepository('SpoutletBundle:DealCode');
        $countries              = Locale::getDisplayCountries('en');

        $user                   = $this->getUser();
        $userAlreadyRedeemed    = false;
        $dealCode               = '';
        $countriesJson          = 'var countries = [';


        /*
         * this is hacky but the only way i could get ddslick jquery plugin to use the selectText property
         * was to inject json into the javascript.
        */
        foreach($countries as $country) {
            $countriesJson .= '{ text: "'.$country.'"},';
        }

        $countriesJson .= '];';

        if (!$deal) {
            throw $this->createNotFoundException('No deal found in this site for slug '.$slug);
        }

        $loggedIn = $this->get('security.context')->isGranted('ROLE_USER');

        $hasKeys = $dealCodeRepo->getTotalAvailableForDeal($deal);

        if ($loggedIn) {

            $currentlyAssigned = $dealCodeRepo->getUserAssignedCodeForDeal($user, $deal);

            if ($currentlyAssigned) {
                $userAlreadyRedeemed = true;
                $dealCode = $currentlyAssigned->getValue();
            }
        }

        $instructions = $deal->getCleanedRedemptionInstructionsArray();
        return array(
            'deal' => $deal,
            'userAlreadyRedeemed' => $userAlreadyRedeemed,
            'dealCode' => $dealCode,
            'redemptionSteps' => $instructions,
            'countries' => $countries,
            'countriesJson' => $countriesJson,
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
        /** @var $dealCodeRepo \Platformd\SpoutletBundle\Entity\DealCodeRepository */
        $dealCodeRepo   = $em->getRepository('SpoutletBundle:DealCode');
        $deal           = $this->getDealManager()->findOneBySlug($slug);
        $clientIp       = $request->getClientIp(true);
        $user           = $this->getUser();
        $locale         = $this->getLocale();
        $pool           = $deal->getActivePool();

        if (!$pool) {
            $this->setFlash('error', 'deal_redeem_no_keys_left');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        if (!$dealCodeRepo->canIpHaveMoreKeys($clientIp, $pool)) {
            $this->setFlash('error', 'deal_redeem_max_ip_hit');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        if ($dealCodeRepo->doesUserHaveCodeForDeal($user, $deal)) {
            $this->setFlash('error', 'deal_redeem_user_already_redeemed');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        $code = $dealCodeRepo->getUnassignedKey($pool);

        if (!$code) {
            $this->setFlash('error', 'deal_redeem_no_keys_left');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        $code->assign($user, $clientIp, $locale);
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

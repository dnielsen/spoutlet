<?php

namespace Platformd\SpoutletBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Locale\Locale;

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
        $allDeals = $this->getDealManager()->findActiveNonFeaturedDeals($featuredDeals);
        $expiredDeals = $this->getDealManager()->findExpiredDeals();

        return array(
            'mainDeal'          => $mainDeal,
            'featuredDeals'     => $featuredDeals,
            'allDeals'          => $allDeals,
            'expiredDeals'      => $expiredDeals,
        );
    }

    /**
     * @param string $slug
     * @Template()
     */
    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $deal = $em->getRepository('SpoutletBundle:Deal')->findOneBySlug($slug);
        $countries = Locale::getDisplayCountries('en');

        if (!$deal) {
            throw $this->createNotFoundException('No deal found in this site for slug '.$slug);
        }

        # figure out if the user has redeemed this deal already, if so pass relevant details to the view here
        $instructions = $deal->getCleanedRedemptionInstructionsArray();
        return array(
            'deal' => $deal,
            'userAlreadyRedeemed' => false,
            'dealCode' => 'alkjshdfljkasdfhaksdf',
            'redemptionSteps' => $instructions,
            'countries' => $countries
        );
    }

    /**
     * @param string $slug
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function redeemAction($slug, Request $request)
    {
        $clientIp = $request->getClientIp(true);

        $maxIpRedeemCountHit = false;

        if ($maxIpRedeemCountHit) {
            $this->setFlash('error', 'deal_redeem_max_ip_hit');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        $userAlreadyRedeemed = false;

        if ($userAlreadyRedeemed) {
            $this->setFlash('error', 'deal_redeem_user_already_redeemed');

            return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
        }

        # redeem the deal here so that when they go back to the show page, the show action can see that this user has redeemed the key

        return $this->redirect($this->generateUrl('deal_show', array('slug' => $slug)));
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\DealManager
     */
    private function getDealManager()
    {
        return $this->get('platformd.model.deal_manager');
    }
}

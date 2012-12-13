<?php

namespace Platformd\SpoutletBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $site = $this->getCurrentSite();

        $featuredDeals = $this->getDealManager()->findFeaturedDeals($site);
        $mainDeal = empty($featuredDeals) ? null : $featuredDeals[0];
        $allDeals = $this->getDealManager()->findActiveDeals($site);
        $expiredDeals = $this->getDealManager()->findExpiredDeals($site);
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
        /*if ($slug == 'ravaged') {
            return $this->render('SpoutletBundle:Deal:showRavaged.html.twig');
        }*/

        $em                     = $this->getDoctrine()->getEntityManager();
        $site                   = $this->getCurrentSite();
        $deal                   = $em->getRepository('SpoutletBundle:Deal')->findOneBySlug($slug, $site);
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
            'hasKeys' => $hasKeys > 0,
            'thread' => $this->getThread(),
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
        $site           = $this->getCurrentSite();
        $dealCodeRepo   = $em->getRepository('SpoutletBundle:DealCode');
        $dealPoolRepo   = $em->getRepository('SpoutletBundle:DealPool');
        $deal           = $this->getDealManager()->findOneBySlug($slug, $site);
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
            $this->setFlash('error', 'deal_redeem_no_keys_for_your_country');
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

    private function getThread()
    {
        $thread = array(
            'id' => 1,
            'can_comment' => true,
            'last_comment_at' => '2012-12-10 11:59:00',
            'permalink' => 'http://www.example.com/news/some-artcle#comments',
            'comments' => array(
                    array(
                            'id'            => 1,
                            'parent_id'     => 0,
                            'author'        => $this->getUser(),
                            'body'          => "Now that there is the Tec-9, a crappy spray gun from South Miami. This gun is advertised as the most popular gun in American crime. Do you believe that shit? It actually says that in the little book that comes with it: the most popular gun in American crime. Like they're actually proud of that shit.",
                            'depth'         => 0,
                            'created_at'    => '2012-12-10 11:44:00',
                            'votes'         => 0,
                            'replies'       => array()
                        ),
                    array(
                            'id'            => 2,
                            'parent_id'     => 0,
                            'author'        => $this->getUser(),
                            'body'          => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras gravida est semper velit ultrices eget tempus purus cursus. Nulla dignissim posuere tristique. In id purus in sapien aliquet dictum quis eget nisl. Fusce non orci est, ut sagittis mi. Curabitur euismod dui vitae massa pharetra vel vestibulum eros sodales. Vivamus scelerisque dictum orci, nec rhoncus risus hendrerit eu. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam erat volutpat.",
                            'depth'         => 0,
                            'created_at'    => '2012-12-10 11:45:00',
                            'votes'         => 0,
                            'replies'       => array()
                        )
                )
        );


        return $thread;
    }

    public function commentsAction(Request $request)
    {
        $thread = array(
            'id' => 1,
            'can_comment' => true,
            'last_comment_at' => '2012-12-10 11:59:00',
            'permalink' => 'http://www.example.com/news/some-artcle#comments',
            'comments' => array(
                    array(
                            'id' => 1,
                            'parent_id' => 0,
                            'author_id' => 3,
                            'body' => 'Now that there is the Tec-9, a crappy spray gun from South Miami. This gun is advertised as the most popular gun in American crime. Do you believe that shit? It actually says that in the little book that comes with it: the most popular gun in American crime. Like they&#39;re actually proud of that shit.',
                            'depth' => 0,
                            'created_at' => '2012-12-10 11:44:00',
                            'votes' => 0,
                            'replies' => array()
                        )
                )
        );

        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');
        $response->setContent(json_encode($thread));
        return $response;
    }
}

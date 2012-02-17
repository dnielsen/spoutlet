<?php

namespace Platformd\SpoutletBundle\Controller;

class DefaultController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Default:index.html.twig');
    }

    /**
     * @param bool $onlyNews Whether to show *just* the news tab
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function featuredContentAction($onlyNews = false)
    {
        $news = $this->getNewsRepo()->findAllForLocale($this->getLocale());

        $allGiveaways = $this->getGiveawayRepo()
            ->findActives($this->getLocale())
        ;

    	return $this->render('SpoutletBundle:Default:featuredContent.html.twig', array(
            'news'          => $news,
            'all_giveaways' => $allGiveaways,
            'onlyNews'      => $onlyNews,
        ));
    }

    public function microsoftAction()
    {
        $image = sprintf('microsoft.%s.jpg', $this->getLocale());
        return $this->render('SpoutletBundle:Default:microsoft.html.twig', array(
            'image' => $image,
        ));
    }

    public function privacyAction()
    {
        return $this->render('SpoutletBundle:Default:privacy.html.twig');
    }

    public function terms_conditionsAction()
    {
        return $this->render('SpoutletBundle:Default:terms_conditions.html.twig');
    }

    public function aboutAction()
    {
        return $this->render('SpoutletBundle:Default:about.html.twig');
    }

    public function contactAction()
    {
        return $this->render('SpoutletBundle:Default:contact.html.twig');
    }

    public function newsAction()
    {
        return $this->render('SpoutletBundle:Default:news.html.twig');
    }

    public function bannerAction()
    {
        $banners = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:HomepageBanner')
            ->findForLocale($this->getLocale());

        return $this->render('SpoutletBundle:Default:banner.html.twig', array('banners' => $banners));
    }
}

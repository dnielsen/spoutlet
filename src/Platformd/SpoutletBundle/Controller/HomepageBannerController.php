<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Form\HomepageBannerType;
use Platformd\SpoutletBundle\Entity\HomepageBanner;
/**
* 
*/
class HomepageBannerController extends Controller
{
    
    public function indexAction()
    {
        $banners = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:HomepageBanner')
            ->findAll();

        return $this->render('SpoutletBundle:HomepageBanner:index.html.twig', array(
            'banners' => $banners
        ));
    }

    public function newAction()
    {   
        $request = $this->getRequest();

        $banner = new HomepageBanner();
        $banner->setLocale($this->getLocale());

        $form = $this->createForm(new HomepageBannerType(), $banner);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $this->getManager()->save($banner);

                $this->setFlash('notice', 'success');
                return $this->redirect($this->generateUrl('admin_homepage_banner_index'));
            }
        }

        return $this->render('SpoutletBundle:HomepageBanner:new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    private function getManager()
    {
        
        return $this->get('platformd.homepage_banner.manager');
    }
}
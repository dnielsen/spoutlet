<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Form\HomepageBannerType;
use Platformd\SpoutletBundle\Entity\HomepageBanner;
/**
* Admin controller for homepage banners
*/
class HomepageBannerController extends Controller
{
    
    public function indexAction()
    {
        $this->addBannersBreadcrumb();

        $banners = $this->getBannerRepo()->findAll();

        return $this->render('SpoutletBundle:HomepageBanner:index.html.twig', array(
            'banners' => $banners
        ));
    }

    public function newAction()
    {
        $this->addBannersBreadcrumb()->addChild('New');
        $request = $this->getRequest();

        $banner = new HomepageBanner();

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

    public function editAction($id)
    {
        $this->addBannersBreadcrumb()->addChild('Edit');
        $request = $this->getRequest();

        $banner = $this->getBannerRepo()->find($id);
        
        if (!$banner) {

            throw $this->createNotFoundException();    
        }

        $form = $this->createForm(new HomepageBannerType(), $banner);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $this->getManager()->save($banner);

                $this->setFlash('notice', 'success');
                return $this->redirect($this->generateUrl('admin_homepage_banner_index'));
            }
        }

        return $this->render('SpoutletBundle:HomepageBanner:edit.html.twig', array(
            'banner' => $banner,
            'form' => $form->createView()
        ));
    }

    public function deleteAction($id)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $banner = $this->getBannerRepo()->find($id);
        
        if (!$banner) {

            throw $this->createNotFoundException();
        }

        $manager->remove($banner);
        $manager->flush();

        $this->setFlash('notice', 'success');
        
        return $this->redirect($this->generateUrl('admin_homepage_banner_index'));
    }

    private function getManager()
    {
        return $this->get('platformd.homepage_banner.manager');
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addBannersBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Homepage Banners', array(
            'route' => 'admin_homepage_banner_index'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\HomepageBannerRepository
     */
    private function getBannerRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:HomepageBanner')
        ;
    }
}
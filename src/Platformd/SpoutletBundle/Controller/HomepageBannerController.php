<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Form\HomepageBannerType;
use Platformd\SpoutletBundle\Entity\HomepageBanner;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
/**
* Admin controller for homepage banners
*/
class HomepageBannerController extends Controller
{

    public function indexAction()
    {
        $this->addBannersBreadcrumb();

        return $this->render('SpoutletBundle:HomepageBanner:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        $this->addBannersBreadcrumb();

        $em = $this->getDoctrine()->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($site);

        $banners = $this->getBannerRepo()->findForSite($site);

        return $this->render('SpoutletBundle:HomepageBanner:list.html.twig', array(
            'banners' => $banners,
            'site' => $site->getId(),
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

                $sitesPositions = $banner->getSitesPositions();

                foreach ($banner->getSites() as $site) {
                    $sitesPositions[$site->getId()] = 0;
                }

                $banner->setSitesPositions($sitesPositions);

                $this->getManager()->save($banner);

                $this->moveAllBannersDown($banner);

                $this->setFlash('success', 'success');
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

        $sitesPositions = $banner->getSitesPositions();

        $sites = $banner->getSites();
        if (!$sites) {
            $site = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($banner->getLocale());
            $banner->getSites()->add($site);
            $sitesPositions[$site->getId()] = 0;
        }

        foreach ($banner->getSites() as $site) {
            if (array_key_exists($site->getId(), $sitesPositions)) {
                continue;
            }

            $sitesPositions[$site->getId()] = 0;
        }

        $form = $this->createForm(new HomepageBannerType(), $banner);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $this->getManager()->save($banner);

                $this->setFlash('success', 'success');
                return $this->redirect($this->generateUrl('admin_homepage_banner_index'));
            }
        }

        return $this->render('SpoutletBundle:HomepageBanner:edit.html.twig', array(
            'banner' => $banner,
            'form' => $form->createView()
        ));
    }

    public function moveAction($id, $site, $direction)
    {
        $em         = $this->getDoctrine()->getEntityManager();
        $bannerRepo = $this->getBannerRepo();
        $site       = $em->getRepository('SpoutletBundle:Site')->find($site);

        $banner     = $bannerRepo->find($id);
        $referer    = $this->getRequest()->headers->get('referer');

        if ($referer) {
            $returnUrl = $referer;
        } else {
            $returnUrl = $this->generateUrl('admin_homepage_banner_index');
        }

        if (!$banner) {

            throw $this->createNotFoundException();
        }

        $positions = $banner->getSitesPositions();

        switch ($direction) {
            case 'up':
                if ($positions[$site->getId()] < 1) {
                    $this->setFlash('error', 'This item cannot move any higher!');
                    return $this->redirect($returnUrl);
                }
                $positions[$site->getId()]--;
                break;

            case 'down':
                $positions[$site->getId()]++;
                break;

            default:

                break;
        }

        $banner->setSitesPositions($positions);

        $this->repositionBanners($banner, $site, $direction);

        $em->persist($banner);
        $em->flush();

        $this->setFlash('success', 'Item moved!');
        return $this->redirect($returnUrl);
    }

    private function moveAllBannersDown($banner)
    {
        $bannerRepo         = $this->getBannerRepo();
        $em                 = $this->getDoctrine()->getEntityManager();

        foreach ($banner->getSites() as $site) {
            $allBannersForSite  = $bannerRepo->findForSite($site);

            foreach ($allBannersForSite as $otherBanner) {

                if ($otherBanner->getId() == $banner->getId()) {
                    continue;
                }

                $otherBannerPositions = $otherBanner->getSitesPositions();
                $otherBannerPositions[$site->getId()]++;
                $otherBanner->setSitesPositions($otherBannerPositions);

                $em->persist($otherBanner);
            }
        }

        $em->flush();
    }

    private function repositionBanners($banner, $site, $direction)
    {
        $bannerRepo = $this->getBannerRepo();
        $allBannersForSite = $bannerRepo->findForSite($site);

        $positions = $banner->getSitesPositions();

        foreach ($allBannersForSite as $otherBanner) {

            if ($otherBanner->getId() == $banner->getId()) {
                continue;
            }

            $otherBannerPositions = $otherBanner->getSitesPositions();

            if ($otherBannerPositions[$site->getId()] == $positions[$site->getId()]) {

                switch ($direction) {
                    case 'up':
                        $otherBannerPositions[$site->getId()]++;
                        break;

                    default:
                        $otherBannerPositions[$site->getId()]--;
                        break;
                }

                $otherBanner->setSitesPositions($otherBannerPositions);
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($otherBanner);
                $em->flush();

                return;
            }
        }
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

        $this->setFlash('success', 'success');

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

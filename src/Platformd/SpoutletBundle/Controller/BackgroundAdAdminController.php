<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Form\Type\BackgroundAdType;
use Platformd\SpoutletBundle\Entity\BackgroundAd;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class BackgroundAdAdminController extends Controller
{
    public function indexAction(Request $request)
    {
        $this->addBackgroundAdBreadcrumb();

        return $this->render('SpoutletBundle:BackgroundAdAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site, Request $request)
    {
        $this->addBackgroundAdBreadcrumb()->addChild($site);

        $em = $this->get('doctrine')->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $site));
        $ads = $this->getRepository()->findBySite($site);

        return $this->render('SpoutletBundle:BackgroundAdAdmin:list.html.twig', array(
            'ads' => $ads,
            'site' => $site,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addBackgroundAdBreadcrumb();

        $ad = new BackgroundAd;
        $form = $this->createForm(new BackgroundAdType(true), $ad);

        return $this->render('SpoutletBundle:BackgroundAdAdmin:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function createAction(Request $request)
    {
        $this->addBackgroundAdBreadcrumb();

        $em     = $this->getDoctrine()->getEntityManager();
        $form   = $this->createForm(new BackgroundAdType(true), new BackgroundAd);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            $ad = $form->getData();

            foreach ($ad->getAdSites() as $adSite) {
                $adSite->setSite($em->getRepository('SpoutletBundle:Site')->find($adSite->getSiteId()));
            }

            if ($form->isValid()) {

                $this->persist($ad);
                $this->setFlash('success', 'Background ad successully created');

                return $this->redirect($this->generateUrl('admin_background_ads_edit', array(
                    'id' => $form->getData()->getId()
                )));
            }
        }

        return $this->render('SpoutletBundle:BackgroundAdAdmin:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id)
    {
        $this->addBackgroundAdBreadcrumb();

        $form = $this->createForm(new BackgroundAdType, $this->findOr404($id));

        return $this->render('SpoutletBundle:BackgroundAdAdmin:edit.html.twig', array(
            'form' => $form->createView(),
            'ad' => $form->getData(),
        ));
    }

    public function updateAction(Request $request, $id)
    {
        $form = $this->createForm(new BackgroundAdType, $this->findOr404($id));

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->persist($form->getData());
                $this->setFlash('success', 'Background ad successully updated');

                return $this->redirect($this->generateUrl('admin_background_ads_edit', array(
                    'id' => $form->getData()->getId()
                )));
            }
        }

        return $this->render('SpoutletBundle:BackgroundAdAdmin:edit.html.twig', array(
            'form' => $form->createView(),
            'ad' => $form->getData(),
        ));
    }

    private function getRepository()
    {
        return $this->get('doctrine')->getRepository('Platformd\SpoutletBundle\Entity\BackgroundAd');
    }

    private function persist(BackgroundAd $ad)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $mUtil = new MediaUtil($em);

        if (!$mUtil->persistRelatedMedia($ad->getFile())) {
            $ad->setFile(null);
        }

        $em->persist($ad);
        $em->flush();
    }

    private function findOr404($id)
    {
        if ($ad = $this->get('doctrine')->getRepository('Platformd\SpoutletBundle\Entity\BackgroundAd')->find($id)) {
            return $ad;
        }

        throw $this->createNotFoundException();
    }

    private function addBackgroundAdBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Background Ads', array(
            'route' => 'admin_background_ads_index'
        ));

        return $this->getBreadcrumbs();
    }
}


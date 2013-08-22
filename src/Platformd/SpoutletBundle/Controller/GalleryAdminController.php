<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Gallery;
use Platformd\SpoutletBundle\Entity\GalleryRepository;
use Platformd\SpoutletBundle\Entity\GalleryCategory;
use Platformd\SpoutletBundle\Entity\GalleryCategoryRepository;
use Platformd\SpoutletBundle\Form\Type\GalleryType;
use Platformd\SpoutletBundle\Form\Type\ImageFindType;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Entity\GalleryTranslation;

/**
 * Gallery admin controller.
 *
 */
class GalleryAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGalleriesBreadcrumb();

        $siteManager = $this->getSiteManager();

        return $this->render('SpoutletBundle:GalleryAdmin:index.html.twig', array(
            'sites' => $siteManager->getSiteChoices(),
        ));
    }

    public function listAction($site)
    {
        $this->addGalleriesBreadcrumb();
        $this->addSiteBreadcrumbs($site);
        $em = $this->getDoctrine()->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->find($site);

        $this->checkGalleryPositions($site, 'image');
        $this->checkGalleryPositions($site, 'video');

        $imageGalleries = $em->getRepository('SpoutletBundle:Gallery')->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'image');
        $videoGalleries = $em->getRepository('SpoutletBundle:Gallery')->findAllGalleriesByCategoryForSiteSortedByPosition($site, 'video');

        return $this->render('SpoutletBundle:GalleryAdmin:list.html.twig', array(
            'imageGalleries'    => $imageGalleries,
            'videoGalleries'    => $videoGalleries,
            'site'              => $site->getId(),
        ));
    }

    public function newAction(Request $request)
    {
        $this->addGalleriesBreadcrumb()->addChild('New Gallery');

        $em = $this->getDoctrine()->getEntityManager();

        $gallery  = new Gallery();
        $form    = $this->createForm(new GalleryType(), $gallery);

        if ($this->processForm($form, $request)) {

            $sitesPositions = $gallery->getSitesPositions();

            foreach ($gallery->getSites() as $site) {
                $sitesPositions[$site->getId()] = 0;
            }

            $gallery->setSitesPositions($sitesPositions);

            $em->persist($gallery);
            $em->flush();

            $this->moveAllGalleriesDown($gallery);

            $this->setFlash('success', 'The gallery was created!');

            return $this->redirect($this->generateUrl('admin_gallery_index'));
        }

        return $this->render('SpoutletBundle:GalleryAdmin:new.html.twig', array(
            'gallery' => $gallery,
            'form'   => $form->createView()
        ));
    }

    public function editAction($slug, Request $request)
    {
        $this->addGalleriesBreadcrumb()->addChild('Edit Gallery');
        $em = $this->getDoctrine()->getEntityManager();

        $gallery = $em->getRepository('SpoutletBundle:Gallery')->findOneBy(array('slug' => $slug));

        if (!$gallery) {
            throw $this->createNotFoundException('Unable to find gallery.');
        }

        $sitesPositions = $gallery->getSitesPositions();

        $sites = $gallery->getSites();
        if (!$sites) {
            $site = $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($gallery->getLocale());
            $gallery->getSites()->add($site);
            $sitesPositions[$site->getId()] = 0;
        }

        $translationSites = array();

        foreach ($gallery->getTranslations() as $translation) {
            $site = $translation->getSite();
            $translationSites[] = $site;
            $translation->setSiteId($site->getId());
        }

        $editForm   = $this->createForm(new GalleryType(), $gallery);

        if ($this->processForm($editForm, $request)) {

            $this->setFlash('success', 'The gallery was saved!');

            return $this->redirect($this->generateUrl('admin_gallery_index'));
        }

        return $this->render('SpoutletBundle:GalleryAdmin:edit.html.twig', array(
            'gallery'      => $gallery,
            'edit_form'   => $editForm->createView(),
        ));
    }

    public function findAction(Request $request) {
        set_time_limit(0); // this page has a tendency to timeout because the search params are always returning large data sets
        $this->addFindImagesBreadcrumb();

        $form       = $this->createForm(new ImageFindType());
        $results    = $this->processFindForm($form, $request);
        $em         = $this->getDoctrine()->getEntityManager();
        $upVotes    = array();
        $likes      = array();

        if($results) {

            $idArray = array();

            foreach ($results as $media) {
                $idArray[] = $media->getId();
                //$likes[$media->getId()] = $this->getEntryLikeCount($media); # removed for now so that the find action won't hang the page due to curl request in getting fb likes.
            }

            $upVotes = $em->getRepository('SpoutletBundle:Vote')->findUpVotesInArray($idArray);
        }

        $this->bindFormValues($form);

        return $this->render('SpoutletBundle:GalleryAdmin:find.html.twig', array(
            'results'   => $results,
            'upVotes'   => $upVotes,
            'form'      => $form->createView()
        ));
    }

    private function processFindForm(Form $form, Request $request)
    {
        $galleryMediaRepo = $this->getDoctrine()->getRepository('SpoutletBundle:GalleryMedia');
        $images = array();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
                    $data['sites'] = array(2); #japanhax! so japan cannot see other site data. or something. anyway, japan's site id is 2
                    $form->setData($data);
                }

                $startDate = $form->get('startDate')->getData();
                $endDate = $form->get('endDate')->getData();
                $sites = $form->get('sites')->getData();

                return $galleryMediaRepo->findImagesForMetrics($data['title'], $data['deleted'], $data['published'], $sites, $startDate, $endDate);
            }
        }

        return null;
    }

    private function bindFormValues(Form $form)
    {
        $data = $form->getData();

        $startDate = $form->get('startDate')->getData();
        $endDate = $form->get('endDate')->getData();

        $formValues = array(
            'title' => $data['title'],
            'published' => $data['published'],
            'deleted' => $data['deleted'],
            'sites' => $data['sites'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $session = $this->getRequest()->getSession();
        $session->set('formValuesGallery', $formValues);
    }

    public function deleteMediaAction($id, Request $request)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getEntityManager();
        $media = $em->getRepository('SpoutletBundle:GalleryMedia')->find($id);

        if(!$media)
        {
            throw $this->createNotFoundException('Media not found');
        }

        if(($media->getAuthor()->getId() != $user->getId()) && !$user->hasRole('ROLE_SUPER_ADMIN'))
        {
            $this->setFlash('error', $this->trans('galleries.delete_photo_error_message'));
            return $this->redirect($this->generateUrl('admin_image_find'));
        }

        $media->setDeleted(true);
        $em->persist($media);
        $em->flush();

        $this->setFlash('success', $this->trans('galleries.delete_photo_success_message'));
        return $this->redirect($this->generateUrl('admin_image_find'));
    }

    public function exportMetricsAction()
    {
        $factory            = new CsvResponseFactory();
        $galleryMediaRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:GalleryMedia');
        $session            = $this->getRequest()->getSession();
        $formValues         = $session->get('formValuesGallery');

        $factory->addRow(array(
            'Title',
            'Status',
            'Owner',
            'First Name',
            'Last Name',
            'Upload Date',
            'Country',
            'Likes',
            'Dislikes',
        ));

        $results = $galleryMediaRepo->findImagesForMetrics($formValues['title'], $formValues['deleted'], $formValues['published'], $formValues['sites'], $formValues['startDate'], $formValues['endDate'], true);

        if($results) {

            foreach ($results as $media) {

                $status     = $media->getPublished() ? 'Published' : 'Unpublished';
                //$likes      = $this->getEntryLikeCount($media); #removed for now since it is very inefficient to make the curl request for each media found.
                $totalVotes = $media->getVotes()->count();

                if ($totalVotes) {
                    $upCount = $media->getVotes()
                        ->filter(function($x) {
                            return
                            $x->getVoteType() == "up"; })
                        ->count();

                    $upVotes = round(($upCount/$totalVotes)*100);
                    $downVotes = round((($totalVotes - $upCount)/$totalVotes)*100);
                } else {
                    $upVotes = 0;
                    $downVotes = 0;
                }

                $factory->addRow(array(
                    $media->getTitle(),
                    $status,
                    $media->getAuthor()->getUsername(),
                    $media->getAuthor()->getFirstname(),
                    $media->getAuthor()->getLastname(),
                    $media->getCreatedAt()->format('Y-m-d H:i:s'),
                    $media->getAuthor()->getCountry(),
                    $upVotes.'%',
                    $downVotes.'%',
                ));
            }
        }

        return $factory->createResponse('Image_Summary.csv');
    }

    public function featuredIndexAction()
    {
        $this->addFeaturedMediaBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $categories = $em->getRepository('SpoutletBundle:GalleryCategory')->findAllAlphabetically();

         return $this->render('SpoutletBundle:GalleryAdmin:featureIndex.html.twig', array(
            'categories' => $categories
        ));
    }

    public function featuredListAction($category)
    {
        $this->addFeaturedMediaBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $items = $em->getRepository('SpoutletBundle:GalleryMedia')->findAllFeaturedForCategory($category);

        return $this->render('SpoutletBundle:GalleryAdmin:featureList.html.twig', array(
            'items'     => $items,
            'category'  => $category,
        ));
    }

    public function unfeatureAction($item)
    {
        $this->addFeaturedMediaBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $item = $em->getRepository('SpoutletBundle:GalleryMedia')->find($item);

        if (!$item) {
            throw $this->createNotFoundException('Unable to find media item.');
        }

        $item->setFeatured(false);
        $em->persist($item);
        $em->flush();

        $this->setFlash('success', 'The item is now unfeatured!');
        return $this->redirect($this->generateUrl('admin_featured_index'));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $gallery = $form->getData();

                $sitesList      = array();
                $sitesPositions = $gallery->getSitesPositions();

                foreach ($gallery->getSites() as $site) {
                    $sitesList[] = $site->getId();

                    if (array_key_exists($site->getId(), $sitesPositions)) {
                        continue;
                    }

                    $sitesPositions[$site->getId()] = 0;
                    $this->moveGalleriesDown($gallery, $site);
                }

                foreach ($sitesPositions as $siteId => $position) {
                    if (!in_array($siteId, $sitesList)) {
                        unset($sitesPositions[$siteId]);
                    }
                }

                $gallery->setSitesPositions($sitesPositions);

                $translationSites = array();

                foreach ($gallery->getTranslations() as $translation) {

                    if (!in_array($translation->getSiteId(), $sitesList)) {
                        $gallery->getTranslations()->removeElement($translation);
                        $em->remove($translation);
                    }

                    $site = $this->getSiteRepository()->find($translation->getSiteId());

                    $translation->setGallery($gallery);

                    if (!$translation->getSite()) {
                        $translation->setSite($site);
                    }

                    $translationSites[] = $site;
                }

                foreach ($gallery->getSites() as $site) {
                    if (!in_array($site, $translationSites)) {
                        $translation = new GalleryTranslation();
                        $translation->setGallery($gallery);
                        $translation->setSite($site);
                        $translation->setSiteId($site->getId());

                        $gallery->getTranslations()->add($translation);
                    }
                }

                if (!$gallery->getOwner()) {
                    $gallery->setOwner($this->getUser());
                }

                $em->persist($gallery);

                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function getEntryLikeCount($galleryMedia)
    {
        /*
        format for getting open graph data:
        http://graph.facebook.com/?ids=http://[site].alienwarearena.com/groups/[$group->getId()]/show/
        */

        $total = 0;

        $url = 'http://graph.facebook.com/?ids=';

        $galleries = $galleryMedia->getGalleries();
        $sites = array();

        foreach ($galleries as $gallery) {
            foreach ($gallery->getSites() as $site) {
                if (!in_array($site, $sites)) {
                    $sites[] = $site;
                }
            }
        }

        foreach($sites as $site)
        {
            $url .= sprintf('http://%s.alienwarearena.com/galleries/photo/%s,', $site->getSubDomain(), $galleryMedia->getId());
        }

        $url = substr($url, 0, -1);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $results = json_decode(curl_exec($curl), true);

        if ($results) {
           foreach($results as $result)
            {
                if(isset($result))
                {
                    if(array_key_exists('likes', $result))
                    {
                        $total += $result['likes'];
                    }
                }
            }
        }

        return $total;
    }

    private function addGalleriesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Galleries', array(
            'route' => 'admin_gallery_index'
        ));

        return $this->getBreadcrumbs();
    }

    private function addFeaturedMediaBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Featured Media', array(
            'route' => 'admin_featured_index'
        ));

        return $this->getBreadcrumbs();
    }

    private function addFindImagesBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Find Images', array(
            'route' => 'admin_image_find'
        ));

        return $this->getBreadcrumbs();
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild($this->getSiteManager()->getSiteName($site), array(
                'route' => 'admin_gallery_list',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    public function moveAction($id, $site, $direction)
    {
        $em             = $this->getDoctrine()->getEntityManager();
        $galleryRepo    = $em->getRepository('SpoutletBundle:Gallery');
        $site           = $em->getRepository('SpoutletBundle:Site')->find($site);

        $gallery        = $galleryRepo->find($id);
        $referer        = $this->getRequest()->headers->get('referer');

        if ($referer) {
            $returnUrl = $referer;
        } else {
            $returnUrl = $this->generateUrl('admin_gallery_index');
        }

        if (!$gallery) {

            throw $this->createNotFoundException();
        }

        $positions = $gallery->getSitesPositions();

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

        $gallery->setSitesPositions($positions);

        $this->repositionGalleries($gallery, $site, $direction);

        $em->persist($gallery);
        $em->flush();

        $this->setFlash('success', 'Item moved!');
        return $this->redirect($returnUrl);
    }

    private function moveGalleriesDown($gallery, $site)
    {
        $em             = $this->getDoctrine()->getEntityManager();
        $galleryRepo    = $em->getRepository('SpoutletBundle:Gallery');

        foreach ($gallery->getCategories() as $category) {
            $allGalleriesForSite    = $galleryRepo->findAllGalleriesByCategoryForSite($site, $category->getName());

            foreach ($allGalleriesForSite as $otherGallery) {

                if ($otherGallery->getId() == $gallery->getId()) {
                    continue;
                }

                $otherGalleryPositions = $otherGallery->getSitesPositions();

                if (isset($otherGalleryPositions[$site->getId()])) {
                    $otherGalleryPositions[$site->getId()]++;
                    $otherGallery->setSitesPositions($otherGalleryPositions);

                    $em->persist($otherGallery);
                }
            }

            $em->flush();
        }
    }

    private function moveAllGalleriesDown($gallery)
    {
        $em             = $this->getDoctrine()->getEntityManager();
        $galleryRepo    = $em->getRepository('SpoutletBundle:Gallery');

        foreach ($gallery->getSites() as $site) {
            foreach ($gallery->getCategories() as $category) {
                $allGalleriesForSite    = $galleryRepo->findAllGalleriesByCategoryForSite($site, $category->getName());

                foreach ($allGalleriesForSite as $otherGallery) {

                    if ($otherGallery->getId() == $gallery->getId()) {
                        continue;
                    }

                    $otherGalleryPositions = $otherGallery->getSitesPositions();

                    if (isset($otherGalleryPositions[$site->getId()])) {
                        $otherGalleryPositions[$site->getId()]++;
                        $otherGallery->setSitesPositions($otherGalleryPositions);

                        $em->persist($otherGallery);
                    }
                }
            }
        }

        $em->flush();
    }

    private function repositionGalleries($gallery, $site, $direction)
    {
        $em                     = $this->getDoctrine()->getEntityManager();
        $galleryRepo            = $em->getRepository('SpoutletBundle:Gallery');

        foreach ($gallery->getCategories() as $category) {
            $allGalleriesForSite    = $galleryRepo->findAllGalleriesByCategoryForSite($site, $category->getName());

            $positions = $gallery->getSitesPositions();

            foreach ($allGalleriesForSite as $otherGallery) {

                if ($otherGallery->getId() == $gallery->getId()) {
                    continue;
                }

                $otherGalleryPositions = $otherGallery->getSitesPositions();

                if ($otherGalleryPositions[$site->getId()] == $positions[$site->getId()]) {

                    switch ($direction) {
                        case 'up':
                            $otherGalleryPositions[$site->getId()]++;
                            break;

                        default:
                            $otherGalleryPositions[$site->getId()]--;
                            break;
                    }

                    $otherGallery->setSitesPositions($otherGalleryPositions);
                    $em = $this->getDoctrine()->getEntityManager();
                    $em->persist($otherGallery);
                    $em->flush();

                    break;
                }
            }
        }
    }

    private function checkGalleryPositions($site, $category) {
        $em             = $this->getDoctrine()->getEntityManager();
        $galleryRepo    = $em->getRepository('SpoutletBundle:Gallery');
        $galleries      = $galleryRepo->findAllGalleriesByCategoryForSite($site, $category);

        $missingPositionArr = array();
        $positionedArr      = array();

        foreach ($galleries as $gallery) {

            if (!$positions = $gallery->getSitesPositions()) {
                $missingPositionArr[] = $gallery;
            } else {
                if (!array_key_exists($positions[$site->getId()], $positionedArr)) {
                    $positionedArr[$positions[$site->getId()]] = $gallery;
                } else {
                    $missingPositionArr[] = $gallery;
                }
            }
        }

        foreach ($missingPositionArr as $gallery) {
            $positionedArr[] = $gallery;
        }

        ksort($positionedArr);
        $newPositions = array_values($positionedArr);

        foreach ($newPositions as $position => $gallery) {
            $positions = $gallery->getSitesPositions();
            $positions[$site->getId()] = $position;
            $gallery->setSitesPositions($positions);

            $em->persist($gallery);
        }

        $em->flush();
    }

    private function getSiteRepository()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Site');
    }

    private function getGalleryRepository()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('SpoutletBundle:Gallery');
    }
}

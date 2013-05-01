<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Gallery;
use Platformd\SpoutletBundle\Entity\GalleryRepository;
use Platformd\SpoutletBundle\Entity\GalleryCategory;
use Platformd\SpoutletBundle\Entity\GalleryCategoryRepository;
use Platformd\SpoutletBundle\Form\Type\GalleryType;
use Platformd\SpoutletBundle\Form\Type\ImageFindType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;

/**
 * Gallery admin controller.
 *
 */
class GalleryAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGalleriesBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $galleries = $em->getRepository('SpoutletBundle:Gallery')->findAllAlphabetically();

        return $this->render('SpoutletBundle:GalleryAdmin:index.html.twig', array(
            'galleries' => $galleries
        ));
    }

    public function newAction(Request $request)
    {
        $this->addGalleriesBreadcrumb()->addChild('New Gallery');

        $gallery  = new Gallery();
        $form    = $this->createForm(new GalleryType(), $gallery);

        if ($this->processForm($form, $request)) {
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
        $this->addFindImagesBreadcrumb();

        $form = $this->createForm(new ImageFindType());
        $results = $this->processFindForm($form, $request);

        $em      = $this->getDoctrine()->getEntityManager();
        $upVotes = array();
        $likes = array();

        if($results) {

            $idArray = array();

            foreach ($results as $media) {
                $idArray[] = $media->getId();
                $likes[$media->getId()] = $this->getEntryLikeCount($media);
            }

            $upVotes   = $em->getRepository('SpoutletBundle:Vote')->findUpVotesInArray($idArray);
        }

        $this->bindFormValues($form);

        return $this->render('SpoutletBundle:GalleryAdmin:find.html.twig', array(
            'results'   => $results,
            'upVotes'   => $upVotes,
            'likes'     => $likes,
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
                    $data['sites'] = array('ja');
                    $form->setData($data);
                }

                $startDate = $form->get('startDate')->getData();
                $endDate = $form->get('endDate')->getData();

                return $galleryMediaRepo->findImagesForMetrics($data['title'], $data['deleted'], $data['published'], $data['sites'], $startDate, $endDate);
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
        $session->set('formValues', $formValues);
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
        $factory = new CsvResponseFactory();
        $galleryMediaRepo = $this->getDoctrine()->getRepository('SpoutletBundle:GalleryMedia');

        $session = $this->getRequest()->getSession();
        $formValues = $session->get('formValues');

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
            'FB Likes',
        ));

        $results = $galleryMediaRepo->findImagesForMetrics($formValues['title'], $formValues['deleted'], $formValues['published'], $formValues['sites'], $formValues['startDate'], $formValues['endDate']);

        if($results) {

            foreach ($results as $media) {

                $status     = $media->getPublished() ? 'Published' : 'Unpublished';
                $likes      = $this->getEntryLikeCount($media);
                $totalVotes =  $media->getVotes()->count();

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
                    $likes,
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
}

<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\MediaGallery;
use Platformd\SpoutletBundle\Entity\GalleryImage;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Gallery controller.
 *
 */
class GalleryController extends Controller
{
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Gallery:index.html.twig');
    }

    public function submitAction()
    {
        $user = $this->getCurrentUser();
        $posting = new GalleryImage();
        $form = $this->createFormBuilder()
            ->getForm();

        $request = $this->getRequest();

        $editId = $this->getRequest()->get('editId');
        if (!preg_match('/^\d+$/', $editId))
        {
            $editId = sprintf('%09d', mt_rand(0, 1999999999));

            if ($posting->getId())
            {
                $this->get('punk_ave.file_uploader')->syncFiles(
                    array('from_folder' => 'attachments/' . $posting->getId(),
                      'to_folder' => 'tmp/attachments/' . $editId,
                      'create_to_folder' => true));
            }
        }

        $existingFiles = array();


        return $this->render('SpoutletBundle:Gallery:submit.html.twig', array(
            'posting'           => $posting,
            'editId'            => $editId,
            'isNew'             => true,
            'form'              => $form->createView(),
            'existingFiles'     => $existingFiles,
        ));
    }

    public function uploadAction()
    {
        $editId = $this->getRequest()->get('editId');
        if (!preg_match('/^\d+$/', $editId))
        {
            throw new Exception("Bad edit id");
        }

        $this->get('punk_ave.file_uploader')->handleFileUpload(array('folder' => 'tmp/attachments/' . $editId));
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }
}

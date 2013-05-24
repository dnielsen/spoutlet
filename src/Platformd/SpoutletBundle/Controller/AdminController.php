<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\MediaBundle\Entity\Media,
    Platformd\MediaBundle\Form\Type\MediaType
;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function indexAction()
    {
        return $this->render('SpoutletBundle:Admin:index.html.twig');
    }

    public function manageMediaAction(Request $request)
    {
        $this->addManageMediaBreadcrumb();

        $page   = $request->query->get('page', 1);
        $pager  = $this->getMediaRepo()->getMediaForAdmin(50, $page);
        $medias = $pager->getCurrentPageResults();

        $media  = new Media();
        $form   = $this->createForm(new MediaType(), $media);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if($form->isValid()) {

                $this->saveMedia($form);
                return $this->redirect($this->generateUrl('admin_upload_media'));
            }
        }

        return $this->render('SpoutletBundle:Admin:manageMedia.html.twig', array(
            'medias' => $medias,
            'pager'   => $pager,
            'form'   => $form->createView(),
        ));
    }

    public function massUnsubscribeAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $form = $this->createFormBuilder()
            ->add('emailsText', 'textarea', array(
                'attr' => array(
                    'class' => 'input-xlarge'
                ),
                'label' => 'Paste Emails',
                'required' => false,
            ))
            ->add('emailsFile', 'file', array(
                'label' => 'Upload Emails (CSV)',
                'required' => false,
            ))
            ->getForm();

        $emailCount = null;

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $data = $form->getData();

                $emailsText = $data['emailsText'];
                $emails  = $emailsText ? explode(',', $emailsText) : array();

                foreach ($emails as $index => $email) {
                    $emails[$index] = trim($email);
                }

                $file = $form->get('emailsFile');
                $file = $file->getData();

                if ($file && ($handle = fopen($file, "r")) !== FALSE) {

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (!in_array($data[0], $emails) && $data[0] != "") {
                            $emails[] = $data[0];
                        }
                    }

                    fclose($handle);
                }

                if (count($emails) == 0) {
                    $this->setFlash('error', 'No email addresses submitted!');
                    return $this->redirect($this->generateUrl('admin_mass_unsubscribe'));
                }

                $users = $em->getRepository('UserBundle:User')->findUserListByEmail($emails);

                if ($users) {
                    foreach ($users as $user) {
                        $user->setSubscribedAlienwareEvents(false);
                        $em->persist($user);
                    }

                    $em->flush();
                }

                $emailCount = count($users);

                $this->setFlash('success', sprintf('%d members successfully unsubscribed!', $emailCount));
                return $this->redirect($this->generateUrl('admin_mass_unsubscribe'));
            }
        }

        return $this->render('SpoutletBundle:Admin:unsubscribe.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function saveMedia($mediaForm)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $media = $mediaForm->getData();
        $media->setOwner($this->getUser());
        $media->setIsAdmin(true);
        $em->persist($media);
        $em->flush();

        $this->setFlash('success', 'Your media was uploaded succesfully');
    }

    private function getMediaRepo()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('MediaBundle:Media');
    }

    private function addManageMediaBreadcrumb()
    {
         $this->getBreadcrumbs()->addChild('Manage Media', array(
            'route' => 'admin_upload_media'
        ));

        return $this->getBreadcrumbs();
    }
}

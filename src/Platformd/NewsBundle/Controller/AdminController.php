<?php

namespace Platformd\NewsBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

use Platformd\NewsBundle\Entity\News;
use Platformd\NewsBundle\Form\Type\CreateNewsFormType;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends Controller
{
    
    public function indexAction()
    {
        $this->addNewsBreadcrumb();

        $manager = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('NewsBundle:News');
        $query = $manager->getFindNewsQuery();
        
        $pager = new PagerFanta(new DoctrineORMAdapter($query));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));
        
        return $this->render('NewsBundle:Admin:index.html.twig', array(
            'news' => $pager
        ));
    }

    public function newAction()
    {
        $this->addNewsBreadcrumb()->addChild('New');
        $news = new News();

        $form = $this->createForm(new CreateNewsFormType(), $news);
        $request = $this->getRequest();

        if ($this->processForm($form, $request)) {
                
            $request
                ->getSession()
                ->setFlash('success', $this->get('translator')->trans('platformd.admin.news.created'));

            return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
        }

        return $this->render('NewsBundle:Admin:new.html.twig', array(
            'form' => $form->createView()
        ));   
    }

    public function editAction($id)
    {
        $this->addNewsBreadcrumb()->addChild('Edit');
        $em = $this->getDoctrine()->getEntityManager();
        $news = $em
            ->getRepository('NewsBundle:News')
            ->findOneBy(array('id' => $id));

        if (!$news) {

            throw $this->createNotFoundException('Unable to retrieve news item #'.$id);
        }

        $form = $this->createForm(new CreateNewsFormType(), $news);
        $request = $this->getRequest();

        if ($this->processForm($form, $request)) {
            $request
                ->getSession()
                ->setFlash('success', $this->get('translator')->trans('platformd.admin.news.modified'));

            return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
        }
        
        return $this->render('NewsBundle:Admin:edit.html.twig', array(
            'form' => $form->createView(),
            'news' => $news
        ));   
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $news = $em
            ->getRepository('NewsBundle:News')
            ->findOneBy(array('id' => $id));
        
        if (!$news) {

            throw $this->createNotFoundException('Unable to retrieve news item #'.$id);
        }

        $em->remove($news);
        $em->flush();

        $this
            ->getRequest()
            ->getSession()
            ->setFlash('success', $this->get('translator')->trans('platformd.admin.news.deleted'));
                
        return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                // either persist the image, or remove it
                /** @var $news \Platformd\NewsBundle\Entity\News */
                $news = $form->getData();
                if ($news->getImage()->getFileObject()) {
                    $em->persist($news->getImage());
                } else {
                    $news->setImage(null);
                }

                $em->persist($news);
                $em->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addNewsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('News', array(
            'route' => 'NewsBundle_admin_homepage'
        ));

        return $this->getBreadcrumbs();
    }
}

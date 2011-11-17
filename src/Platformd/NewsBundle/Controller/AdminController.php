<?php

namespace Platformd\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Platformd\NewsBundle\Entity\News;
use Platformd\NewsBundle\Form\Type\CreateNewsFormType;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class AdminController extends Controller
{
    
    public function indexAction()
    {
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
        $news = new News();

        $form = $this->createForm(new CreateNewsFormType(), $news);
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getEntityManager();

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);
            
            if ($form->isValid()) {
                $em->persist($news);
                $em->flush();
                
                $request
                    ->getSession()
                    ->setFlash('notice', 'Your news item has been created!');
                
                return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
            }
        }

        return $this->render('NewsBundle:Admin:new.html.twig', array(
            'form' => $form->createView()
        ));   
    }
}

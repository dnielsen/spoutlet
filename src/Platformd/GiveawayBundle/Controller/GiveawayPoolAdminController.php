<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Form\Type\GiveawayPoolType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
* 
*/
class GiveawayPoolAdminController extends Controller
{
    
    /**
     * Index action for Giveway pools management
     */
    public function indexAction()
    {
        $pools = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:GiveawayPool')
            ->findAll();

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:index.html.twig', array(
            'pools' => $pools
        ));
    }

    public function newAction()
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();      
        $pool = new GiveawayPool();
        $request = $this->getRequest();

        $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_poll_index'));
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function editAction($id)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('GiveawayBundle:GiveawayPool')
            ->findOneBy(array('id' => $id));

        if (!$pool) {
            throw $this->createNotFoundException();
        }

        $request = $this->getRequest();

         $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_poll_index'));
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView()
        ));
    }

    /**
     * Save a pool & add keys stored in the uploaded file
     *
     * @param \Platformd\GiveawayBundle\Form\Type\GiveawayPoolType $pool
     */
    protected function savePool(GiveawayPool $pool) 
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        if ($loadedKeys = $pool->loadKeysFromFile()) {
            foreach ($loadedKeys as $key) {
                $manager->persist($key);
            }
        }
        
        $manager->persist($pool);
        $manager->flush();

        $this
            ->getRequest()
            ->getSession()
            ->setFlash('notice', $this->get('translator')->trans('platformd.giveaway_pool.admin.saved'));
    }
}
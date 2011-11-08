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
    public function indexAction($giveaway)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $giveaway = $manager
            ->getRepository('GiveawayBundle:Giveaway')
            ->findOneBy(array('id' => $giveaway));

        if (!$giveaway) {
            
            throw $this->createNotFoundException();    
        }

        $pools = $manager
            ->getRepository('GiveawayBundle:GiveawayPool')
            ->findBy(array('giveaway' => $giveaway->getId()));

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:index.html.twig', array(
            'pools'     => $pools,
            'giveaway'  => $giveaway
        ));
    }

    public function newAction($giveaway)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();      

        $giveaway = $this->retrieveGiveawayById($giveaway);

        $pool = new GiveawayPool();
        $pool->setGiveaway($giveaway);

        $request = $this->getRequest();

        $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_poll_index', array(
                    'giveaway' => $giveaway->getId()
                )));
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:new.html.twig', array(
            'form'      => $form->createView(),
            'giveaway'  => $giveaway
        ));
    }

    public function editAction($giveaway, $pool)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('GiveawayBundle:GiveawayPool')
            ->findOneBy(array('id' => $pool));

        if (!$pool) {
            throw $this->createNotFoundException();
        }

        $request = $this->getRequest();

         $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_poll_index', array(
                    'giveaway' => $giveaway
                )));
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView()
        ));
    }

    public function deleteAction($giveaway, $pool)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('GiveawayBundle:GiveawayPool')
            ->findOneBy(array('id' => $pool));

        if (!$pool) {
            
            throw $this->createNotFoundException();
        }

        $manager->remove($pool);
        $manager->flush();

        return $this->redirect($this->generateUrl('admin_giveaway_poll_index', array(
            'giveaway' => $giveaway
        )));
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

    /**
     * Retrieve a Giveaway using its id
     *
     * @param integer $id
     * @return \Platformd\GiveawayBundle\Entity\Giveaway
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function retrieveGiveawayById($id)
    {
        $giveaway = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway')
            ->findOneBy(array('id' => $id));
        
        if (!$giveaway) {
            
            throw $this->createNotFoundException();
        }

        return $giveaway;
    }
}
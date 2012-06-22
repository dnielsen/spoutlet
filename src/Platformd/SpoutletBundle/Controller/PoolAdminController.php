<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\DealPool;
use Platformd\SpoutletBundle\Form\Type\DealPoolType;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\SpoutletBundle\Entity\Deal;

/**
*
*/
class DealPoolAdminController extends Controller
{

    /**
     * Index action for Giveway pools management
     */
    public function indexAction($deal)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $deal = $this->retrieveDealById($deal);
        $this->addDealBreadcrumb($deal);

        $pools = $manager
            ->getRepository('SpoutletBundle:DealPool')
            ->findBy(array('deal' => $deal->getId()));

        return $this->render('SpoutletBundle:DealPoolAdmin:index.html.twig', array(
            'pools'     => $pools,
            'deal'  => $deal,
            'keyRepo'   => $this->getDealCodeRepository(),
        ));
    }

    public function newAction($deal)
    {
        $deal = $this->retrieveDealById($deal);
        $this->addDealBreadcrumb($deal)->addChild('New Pool');

        $pool = new DealPool();
        $pool->setDeal($deal);

        $request = $this->getRequest();

        $form = $this->createForm(new DealPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
                    'deal' => $deal->getId()
                )));
            }

            var_dump($form->createView()->get('errors'));die;
        }

        return $this->render('SpoutletBundle:DealPoolAdmin:new.html.twig', array(
            'form'      => $form->createView(),
            'dealId'  => $deal->getId()
        ));
    }

    public function editAction($deal, $pool)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('SpoutletBundle:DealPool')
            ->findOneBy(array('id' => $pool));

        if (!$pool) {
            throw $this->createNotFoundException();
        }

        $this->addDealBreadcrumb($pool->getDeal())->addChild('Edit Pool');

        $request = $this->getRequest();

         $form = $this->createForm(new DealPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
                    'deal' => $deal
                )));
            }
        }

        return $this->render('SpoutletBundle:DealPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView(),
            'dealId' => $deal,
        ));
    }

    public function deleteAction($deal, $pool)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('SpoutletBundle:DealPool')
            ->findOneBy(array('id' => $pool));

        if (!$pool) {

            throw $this->createNotFoundException();
        }

        $manager->remove($pool);
        $manager->flush();

        return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
            'deal' => $deal
        )));
    }

    /**
     * Save a pool & add keys stored in the uploaded file
     *
     * @param \Platformd\SpoutletBundle\Form\Type\DealPoolType $pool
     */
    protected function savePool(DealPool $pool)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($pool);
        $em->flush();

        if ($pool->getCodesfile()) {
            $loader = new \Platformd\SpoutletBundle\Pool\PoolLoader($this->get('database_connection'));
            $loader->loadCodesFromFile($pool->getCodesfile(), $pool);
        }

        $this->setFlash('success', 'platformd.deal_pool.admin.saved');
    }

    /**
     * Retrieve a Deal using its id
     *
     * @param integer $id
     * @return \Platformd\SpoutletBundle\Entity\Deal
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function retrieveDealById($id)
    {
        $deal = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('SpoutletBundle:Deal')
            ->findOneBy(array('id' => $id));

        if (!$deal) {

            throw $this->createNotFoundException();
        }

        return $deal;
    }

    /**
     * @return \Platformd\SpoutletBundle\Entity\Repository\DealCodeRepository
     */
    protected function getDealCodeRepository()
    {
        return $this->getDoctrine()
            ->getRepository('SpoutletBundle:DealCode')
        ;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addDealBreadcrumb(Deal $deal)
    {
        $this->getBreadcrumbs()->addChild('Deals', array(
            'route' => 'admin_deal_index'
        ));

        $this->getBreadcrumbs()->addChild($deal->getName(), array(
            'route' => 'admin_deal_edit',
            'routeParameters' => array('id' => $deal->getId())
        ));

        $this->getBreadcrumbs()->addChild('Code Pools', array(
            'route' => 'admin_deal_pool_index',
            'routeParameters' => array('deal' => $deal->getId())
        ));

        return $this->getBreadcrumbs();
    }
}

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
     * Index action for Deal pools management
     */
    public function indexAction($dealId)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $deal = $this->retrieveDealById($dealId);
        $this->addDealBreadcrumb($deal);

        $pools = $manager
            ->getRepository('SpoutletBundle:DealPool')
            ->findBy(array('deal' => $deal->getId()));

        return $this->render('SpoutletBundle:DealPoolAdmin:index.html.twig', array(
            'pools'     => $pools,
            'deal'  => $deal,
            'codeRepo'   => $this->getDealCodeRepository(),
        ));
    }

    public function newAction($dealId)
    {
        $deal = $this->retrieveDealById($dealId);
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
                    'dealId' => $deal->getId(),
                )));
            }

            var_dump($form->createView()->get('errors'));die;
        }

        return $this->render('SpoutletBundle:DealPoolAdmin:new.html.twig', array(
            'form'      => $form->createView(),
            'dealId'  => $deal->getId()
        ));
    }

    public function editAction($dealId, $poolId)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('SpoutletBundle:DealPool')
            ->findOneBy(array('id' => $poolId));

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
                    'dealId' => $dealId
                )));
            }
        }

        return $this->render('SpoutletBundle:DealPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView(),
            'dealId' => $dealId,
        ));
    }

    public function deleteAction($dealId, $poolId)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('SpoutletBundle:DealPool')
            ->findOneBy(array('id' => $poolId));

        if (!$pool) {

            throw $this->createNotFoundException();
        }

        $manager->remove($pool);
        $manager->flush();

        return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
            'dealId' => $dealId
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

        if ($pool->getKeysfile()) {
            $loader = new \Platformd\GiveawayBundle\Pool\PoolLoader($this->get('database_connection'));
            $loader->loadKeysFromFile($pool->getKeysfile(), $pool, 'DEAL');
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
            'route' => 'admin_deal'
        ));

        $this->getBreadcrumbs()->addChild($deal->getName(), array(
            'route' => 'admin_deal_edit',
            'routeParameters' => array('id' => $deal->getId())
        ));

        $this->getBreadcrumbs()->addChild('Pools', array(
            'route' => 'admin_deal_pool_index',
            'routeParameters' => array('dealId' => $deal->getId())
        ));

        return $this->getBreadcrumbs();
    }
}

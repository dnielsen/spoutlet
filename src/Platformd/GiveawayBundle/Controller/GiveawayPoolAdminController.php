<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Form\Type\GiveawayPoolType;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\GiveawayBundle\Entity\Giveaway;

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

        $giveaway = $this->retrieveGiveawayById($giveaway);
        $this->addGiveawayBreadcrumb($giveaway);

        $pools = $manager
            ->getRepository('GiveawayBundle:GiveawayPool')
            ->findBy(array('giveaway' => $giveaway->getId()));

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:index.html.twig', array(
            'pools'     => $pools,
            'giveaway'  => $giveaway,
            'keyRepo'   => $this->getGiveawayKeyRepository(),
        ));
    }

    public function newAction($giveaway)
    {
        $giveaway = $this->retrieveGiveawayById($giveaway);
        $this->addGiveawayBreadcrumb($giveaway)->addChild('New Pool');

        $pool = new GiveawayPool();
        $pool->setGiveaway($giveaway);

        $request = $this->getRequest();

        $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_pool_index', array(
                    'giveaway' => $giveaway->getId()
                )));
            }

            var_dump($form->createView()->get('errors'));die;
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:new.html.twig', array(
            'form'      => $form->createView(),
            'giveawayId'  => $giveaway->getId()
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

        $this->addGiveawayBreadcrumb($pool->getGiveaway())->addChild('Edit Pool');

        $request = $this->getRequest();

         $form = $this->createForm(new GiveawayPoolType(), $pool);

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $this->savePool($pool);

                return $this->redirect($this->generateUrl('admin_giveaway_pool_index', array(
                    'giveaway' => $giveaway
                )));
            }
        }

        return $this->render('GiveawayBundle:GiveawayPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView(),
            'giveawayId' => $giveaway,
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

        return $this->redirect($this->generateUrl('admin_giveaway_pool_index', array(
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
        $em = $this->getDoctrine()->getEntityManager();

        $ruleset    = $pool->getRuleset();
        $rules      = $ruleset->getRules();

        $newRulesArray = array();

        $defaultAllow = true;

        foreach ($rules as $rule) {
            if ($rule->getCountry()) {
                $rule->setRuleset($ruleset);
                $newRulesArray[] = $rule;

                $defaultAllow = $rule->getRuleType() == "allow" ? false : true;
            }
        }

        $oldRules = $em->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

        if ($oldRules) {
            foreach ($oldRules as $oldRule) {
                if (!in_array($oldRule, $newRulesArray)) {
                    $oldRule->setRuleset(null);
                }
            }
        }

        $pool->getRuleset()->setParentType('giveaway-pool');
        $pool->getRuleset()->setDefaultAllow($defaultAllow);

        $em->persist($pool);
        $em->flush();

        if ($pool->getKeysfile()) {
            $loader = new \Platformd\GiveawayBundle\Pool\PoolLoader($this->get('database_connection'));
            $loader->loadKeysFromFile($pool->getKeysfile(), $pool);
        }

        $this->setFlash('success', 'platformd.giveaway_pool.admin.saved');
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

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    protected function getGiveawayKeyRepository()
    {
        return $this->getDoctrine()
            ->getRepository('GiveawayBundle:GiveawayKey')
        ;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGiveawayBreadcrumb(Giveaway $giveaway)
    {
        $this->getBreadcrumbs()->addChild('Giveaways', array(
            'route' => 'admin_giveaway_index'
        ));

        $this->getBreadcrumbs()->addChild($giveaway->getName(), array(
            'route' => 'admin_giveaway_edit',
            'routeParameters' => array('id' => $giveaway->getId())
        ));

        $this->getBreadcrumbs()->addChild('Key Pools', array(
            'route' => 'admin_giveaway_pool_index',
            'routeParameters' => array('giveaway' => $giveaway->getId())
        ));

        return $this->getBreadcrumbs();
    }
}

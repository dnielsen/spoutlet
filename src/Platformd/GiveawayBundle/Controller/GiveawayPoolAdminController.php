<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Form\Type\GiveawayPoolType;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\QueueMessage\KeyPoolQueueMessage;

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
                $result = $this->savePool($pool);

                if ($result) {
                    return $this->redirect($this->generateUrl('admin_giveaway_pool_index', array(
                        'giveaway' => $giveaway->getId()
                    )));
                }
            }
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
                $result = $this->savePool($pool);

                if ($result) {
                    return $this->redirect($this->generateUrl('admin_giveaway_pool_index', array(
                        'giveaway' => $giveaway
                    )));
                }
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

        $keysFile = $pool->getKeysfile();

        if ($keysFile) {

            if ($keysFile->getSize() > GiveawayPool::POOL_SIZE_QUEUE_THRESHOLD) {

                $s3         = $this->container->get('aws_s3');
                $bucket     = $this->container->getParameter('s3_bucket_name');

                $handle     = fopen($keysFile, 'r');
                $filename   = trim(GiveawayPool::POOL_FILE_S3_PREFIX, '/').'/'.md5_file($keysFile).'.'.pathinfo($keysFile->getClientOriginalName(), PATHINFO_EXTENSION);

                $response = $s3->create_object($bucket, $filename, array(
                    'fileUpload'    => $handle,
                    'acl'           => \AmazonS3::ACL_PRIVATE,
                    'encryption'    => 'AES256',
                    'contentType'   => 'text/plain',
                ));

                if ($response->isOk()) {

                    $message = new KeyPoolQueueMessage();
                    $message->bucket    = $bucket;
                    $message->filename  = $filename;
                    $message->siteId    = $this->getCurrentSite()->getId();
                    $message->userId    = $this->getUser()->getId();
                    $message->poolId    = $pool->getId();
                    $message->poolClass = implode('', array_slice(explode('\\', get_class($pool)), -1, 1));

                    $sqs = $this->container->get('aws_sqs');
                    $queue_url = $this->container->getParameter('queue_prefix').KeyPoolQueueMessage::QUEUE_NAME;
                    $queue_response = $sqs->send_message($queue_url, json_encode($message));

                    $this->setFlash($queue_response->isOk() ? 'success' : 'error', $queue_response->isOk() ? 'platformd.giveaway_pool.admin.queued' : 'platformd.giveaway_pool.admin.queue_error');
                    return $queue_response->isOk() ? true : false;
                } else {
                    $this->setFlash('error', 'platformd.giveaway_pool.adminupload_error');
                    return false;
                }

            } else {

                $loader = new \Platformd\GiveawayBundle\Pool\PoolLoader($this->get('database_connection'));
                $loader->loadKeysFromFile($pool->getKeysfile(), $pool);
                $this->setFlash('success', 'platformd.giveaway_pool.admin.saved');
                return true;
            }
        }
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

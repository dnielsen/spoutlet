<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\DealPool;
use Platformd\GiveawayBundle\Form\Type\DealPoolType;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\GiveawayBundle\Entity\Deal;
use Symfony\Component\Locale\Locale;
use Platformd\GiveawayBundle\QueueMessage\KeyPoolQueueMessage;

use HPCloud\HPCloudPHP;
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
            ->getRepository('GiveawayBundle:DealPool')
            ->findBy(array('deal' => $deal->getId()));

        return $this->render('GiveawayBundle:DealPoolAdmin:index.html.twig', array(
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
                $result = $this->savePool($pool);

                if ($result) {
                    return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
                        'dealId' => $deal->getId(),
                    )));
                }
            }
        }

        $countries = Locale::getDisplayCountries('en');

        return $this->render('GiveawayBundle:DealPoolAdmin:new.html.twig', array(
            'form'      => $form->createView(),
            'dealId'  => $deal->getId(),
            'countries' => $countries
        ));
    }

    public function editAction($dealId, $poolId)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('GiveawayBundle:DealPool')
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
                
                $result = $this->savePool($pool);

                if ($result) {
                    return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
                        'dealId' => $dealId
                    )));
                }
            }
        }

        $countries = Locale::getDisplayCountries('en');

        return $this->render('GiveawayBundle:DealPoolAdmin:edit.html.twig', array(
            'pool' => $pool,
            'form' => $form->createView(),
            'dealId' => $dealId,
            'countries' => $countries
        ));
    }

    public function deleteAction($dealId, $poolId)
    {
        $manager = $this
            ->getDoctrine()
            ->getEntityManager();

        $pool = $manager->getRepository('GiveawayBundle:DealPool')
            ->findOneBy(array('id' => $poolId));

        if (!$pool) {

            throw $this->createNotFoundException();
        }

        $manager->remove($pool);
        $manager->flush();

        $this->banCaches($pool);

        return $this->redirect($this->generateUrl('admin_deal_pool_index', array(
            'dealId' => $dealId
        )));
    }

    protected function savePool(DealPool $pool)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($pool);
        $em->flush();
      
        $keysFile = $pool->getKeysfile();
        
        if ($keysFile) {
          
            
            //if ($keysFile->getSize() > DealPool::POOL_SIZE_QUEUE_THRESHOLD) {
              if ($keysFile->getSize() > 21) {

                $s3         = $this->container->get('aws_s3');
               // $bucket     = $this->container->getParameter('s3_private_bucket_name');
                $bucket     = $this->container->getParameter('s3_bucket_name');

                $handle     = fopen($keysFile, 'r');
                $filename   = trim(DealPool::POOL_FILE_S3_PREFIX, '/').'/'.md5_file($keysFile).'.'.pathinfo($keysFile->getClientOriginalName(), PATHINFO_EXTENSION);

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

                    if($this->container->getParameter('object_storage') == 'HpObjectStorage'){
                     $this->hpCloudObj = new HPCloudPHP($this->container->getParameter('hpcloud_accesskey'),$this->container->getParameter('hpcloud_secreatkey'),$this->container->getParameter('hpcloud_tenantid'));
                     $queue_response = $this->hpCloudObj->sendMessageToQueue(KeyPoolQueueMessage::QUEUE_NAME, json_encode($message));
                     $queue_response_data = json_decode($queue_response);
                    $queue_response_id = $queue_response_data->{'id'}; 
                     $this->setFlash($queue_response_id != '' ? 'success' : 'error', $queue_response_id != ''  ? 'platformd.giveaway_pool.admin.queued' : 'platformd.giveaway_pool.admin.queue_error');
                     return $queue_response_id ? true : false;

                    } else {  
                    $sqs = $this->container->get('aws_sqs');
                    $queue_url = $this->container->getParameter('queue_prefix').KeyPoolQueueMessage::QUEUE_NAME;
                    $queue_response = $sqs->send_message($queue_url, json_encode($message));
          
                    $this->setFlash($queue_response->isOk() ? 'success' : 'error', $queue_response->isOk() ? 'platformd.giveaway_pool.admin.queued' : 'platformd.giveaway_pool.admin.queue_error');
                    return $queue_response->isOk() ? true : false;
                   }
                } else {
                
                    $this->setFlash('error', 'platformd.giveaway_pool.adminupload_error');
                    return false;
                }

            } else {
              
                $loader = new \Platformd\GiveawayBundle\Pool\PoolLoader($this->get('database_connection'));
                $loader->loadKeysFromFile($pool->getKeysfile(), $pool, 'DEAL');
                $this->setFlash('success', 'platformd.deal_pool.admin.saved');

                $this->banCaches($pool);

                return true;
            }
            
        }
       
        $this->banCaches($pool);
    }

    /**
     * Retrieve a Deal using its id
     *
     * @param integer $id
     * @return \Platformd\GiveawayBundle\Entity\Deal
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function retrieveDealById($id)
    {
        $deal = $this
            ->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Deal')
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
            ->getRepository('GiveawayBundle:DealCode')
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

    private function banCaches($pool)
    {
        $varnishUtil  = $this->getVarnishUtil();
        $indexPath    = $this->generateUrl('deal_list');
        $dealPath     = $this->generateUrl('deal_show', array('slug' => $pool->getDeal()->getSlug()));

        try {
            $varnishUtil->banCachedObject($indexPath);
            $varnishUtil->banCachedObject($dealPath);
        } catch (\Exception $e) {
            throw new \Exception('Could not ban.');
        }
    }
}

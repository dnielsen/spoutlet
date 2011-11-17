<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Form\Type\GiveawayType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

class GiveawayAdminController extends Controller
{
    public function indexAction()
    {
        $giveaways = $this->getGiveawayRepo()->findBy(array(
            'locale' => $this->get('session')->getLocale(),
        ));

    	return $this->render('GiveawayBundle:GiveawayAdmin:index.html.twig',
            array('giveaways' => $giveaways));
    }

    public function newAction(Request $request)
    {
    	$giveaway = new Giveaway();
        // give them some sample stuff
        $giveaway->setRedemptionInstructions(<<<EOT
Open EA's Origin client (or download it <a href="http://www.origin.com/about">here</a> if you don't already have it installed).
Click the cog icon, select "Redeem Product Code" and enter your dog tag code.
Your dog tog will be unlocked the next time you play Battlefield 3.
EOT
        );

    	$form = $this->createForm(new GiveawayType(), $giveaway);

    	if($request->getMethod() == 'POST')
    	{
    		$form->bindRequest($request);

    		if($form->isValid())
    		{
    			$this->saveGiveaway($form);
    			return $this->redirect($this->generateUrl('admin_giveaway_edit', array('id' => $giveaway->getId())));
    		}
    	}

    	return $this->render('GiveawayBundle:GiveawayAdmin:new.html.twig', array('form' => $form->createView(),));
    }

    public function editAction(Request $request, $id)
    {
        $giveaway = $this->getGiveawayRepo()->findOneById($id);

        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $form = $this->createForm(new GiveawayType(), $giveaway);

        if($request->getMethod() == 'POST')
        {
        	$form->bindRequest($request);

        	if($form->isValid())
        	{
        		$this->saveGiveaway($form);
        		return $this->redirect($this->generateUrl('admin_giveaway_edit', array('id' => $giveaway->getId())));
        	}
        }

    	return $this->render('GiveawayBundle:GiveawayAdmin:edit.html.twig',
    		array('form' => $form->createView(), 'giveaway' => $giveaway));
    }

    protected function retrieveGiveaway($id)
    {
        if (!$giveaway = $this->getGiveawayRepo()->find($id)) {
            throw $this->createNotFoundException();
        }

        return $giveaway;
    }

    private function saveGiveaway(Form $giveawayForm)
    {
        // save to db
        $giveaway = $giveawayForm->getData();
        $giveaway->setLocale($this->get('session')->getLocale());

        $this
            ->get('platformd.events_manager')
            ->save($giveaway);
            
        $this
            ->getRequest()
            ->getSession()
            ->setFlash('notice', $this->get('translator')->trans('platformd.giveaway.admin.saved'));
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    private function getGiveawayRepo()
    {
        return $this->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }
}

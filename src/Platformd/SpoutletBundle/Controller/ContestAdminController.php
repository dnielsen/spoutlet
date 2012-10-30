<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\ContestRepository;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Form\Type\ContestType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Knp\MediaBundle\Util\MediaUtil;

class ContestAdminController extends Controller
{
    public function indexAction()
    {
        $this->addContestsBreadcrumb();

        return $this->render('SpoutletBundle:ContestAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        $this->addContestsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $contests = $em->getRepository('SpoutletBundle:Contest')->findAllForSiteAlphabetically($site);

        return $this->render('SpoutletBundle:ContestAdmin:list.html.twig', array(
            'contests' => $contests
        ));
    }

    public function newAction(Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('New Contest');

        $contest  = new Contest();
        $form    = $this->createForm(new ContestType(), $contest);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The contest was created!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:new.html.twig', array(
            'contest' => $contest,
            'form'   => $form->createView()
        ));
    }

    public function editAction($slug, Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('Edit Contest');
        $em = $this->getDoctrine()->getEntityManager();

        $contest = $em->getRepository('SpoutletBundle:Contest')->findOneBy(array('slug' => $slug));

        if (!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $editForm   = $this->createForm(new ContestType(), $contest);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The contest was saved!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:edit.html.twig', array(
            'contest'       => $contest,
            'edit_form'     => $editForm->createView(),
        ));
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $contest = $form->getData();

                $formRules = $contest->getRuleset();
                $formRules = $formRules['rules'];

                $ruleset = new CountryAgeRestrictionRuleset();

                $ruleset->setParentType("contest");
                $ruleset->setDefaultAllow(true);
                $rules = $ruleset->getRules();

                foreach ($formRules as $rule) {

                    $r = new CountryAgeRestrictionRule();
                    $r->setCountry($rule['country']);
                    $r->setRuleset($ruleset);
                    $r->setMinAge($rule['minAge']);
                    $r->setMaxAge($rule['maxAge']);
                    $r->setRuleType($rule['ruleType']);

                    $rules->add($r);
                }

                $contest->setRuleset($ruleset);
var_dump($contest->getRuleset());exit;
                $mUtil = new MediaUtil($this->getDoctrine()->getEntityManager());

                if (!$mUtil->persistRelatedMedia($contest->getBanner())) {
                    $contest->setBanner(null);
                }

                $em->persist($contest);

                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function addContestsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_index'
        ));

        return $this->getBreadcrumbs();
    }
}

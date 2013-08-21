<?php

namespace Platformd\SweepstakesBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\SweepstakesEntry;
use Platformd\SweepstakesBundle\Entity\SweepstakesAnswer;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesEntryType;

use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontendController extends Controller
{
    public function _sweepstakesFlashMessageAction($slug, $entryId = null)
    {
        $sweepstakes = $this->findSweepstakes($slug, false);

        if ($entryId) {
            $assignedEntry = $this->getEntryRepo()->findOneByIdAndUser($entryId, $this->getUser());
        } else {
            $assignedEntry = null;
        }

        if ($assignedEntry) { # the user has a key, so let's display it for them

            $response = $this->render('SweepstakesBundle:Frontend:_showKey.html.twig', array(
                'sweepstakes' => $sweepstakes
            ));

            $this->varnishCache($response, 60);

            return $response;
        }

        return new Response();
    }

    public function indexAction()
    {
        $sweepstakess = $this->getSweepstakesRepo()->findPublished($this->getCurrentSite());

        return $this->render('SweepstakesBundle:Frontend:index.html.twig', array(
            'sweepstakess' => $sweepstakess
        ));
    }

    /**
     * @Template
     * @param integer $entryId The optiona entrance id that was just assigned
     * @param $slug
     * @return array
     */
    public function showAction($slug, $entryId = null, Request $request)
    {
        $sweepstakes   = $this->findSweepstakes($slug, false);
        $user          = $this->getCurrentUser();
        $isGroupMember = null;
        $em            = $this->getDoctrine()->getEntityManager();

        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if ((!$sweepstakes->getPublished() || !$sweepstakes->isCurrentlyOpen()) && !$canTest) {
            throw $this->createNotFoundException();
        }

        $entry = new SweepstakesEntry($sweepstakes);

        if (!$this->isGranted('ROLE_USER')) {
            $isEntered = false;
        } else {
            $isEntered     = (bool) $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $user);
            $isGroupMember = $sweepstakes->getGroup() ? $this->getGroupManager()->isMember($user, $sweepstakes->getGroup()) : null;
            $entry->setUser($user);
        }

        foreach ($sweepstakes->getQuestions() as $question) {
            $entry->addAnswer(new SweepstakesAnswer($question, $entry));
        }

        $entryForm    = $this->createForm('platformd_sweeps_entry', $entry);
        $formHandler  = $this->container->get('platformd_sweeps.entry.form.handler');
        $formHandler->setForm($entryForm);

        $process = $formHandler->process(true);

        if($process) {

            $existing = $user ? $this->getEntryRepo()->findOneBySweepstakesAndUser($sweepstakes, $user) : null;
            if ($existing) {
                $this->setFlash('error', 'already_entered_sweepstakes');
                return $this->redirectToShow($sweepstakes);
            }

            return $this->redirect($this->generateUrl('sweepstakes_show', array('slug' => $slug)));
        }

        return array(
            'sweepstakes'       => $sweepstakes,
            'isEntered'         => $isEntered,
            'isGroupMember'     => $isGroupMember,
            'entryId'           => $entryId,
            'entryForm'         => $entryForm->createView(),
            'errors'            => $this->getEntryFormErrors($entryForm),
        );
    }

    public function rulesAction(Request $request, $slug)
    {
        $sweepstakes    = $this->findSweepstakes($slug, false);
        $canTest        = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if ((!$sweepstakes->getPublished() || !$sweepstakes->isCurrentlyOpen()) && !$canTest) {
            throw $this->createNotFoundException();
        }

        return $this->render('SweepstakesBundle:Frontend:rules.html.twig', array(
            'sweepstakes' => $sweepstakes,
        ));
    }

    /**
     * @param $slug
     * @param integer $restrictUnpublished
     * @return \Platformd\SweepstakesBundle\Entity\Sweepstakes
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findSweepstakes($slug, $restrictUnpublished = true)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOneBySlugForSite($slug, $this->getCurrentSite());

        if (!$sweepstakes) {
            throw $this->createNotFoundException('No sweepstakes for slug '.$slug);
        }

        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if (($restrictUnpublished && !$sweepstakes->getPublished()) && !$canTest) {
            throw $this->createNotFoundException('But this sweepstakes is not published! '.$slug);
        }

        return $sweepstakes;
    }

    private function redirectToShow(Sweepstakes $sweepstakes)
    {
        return $this->redirect($this->generateUrl(
            'sweepstakes_show',
            array('slug' => $sweepstakes->getSlug())
        ));
    }

    private function getEntryFormErrors($form)
    {
        if ($form->isBound()) {
            $errors = array();
            foreach ($form->getErrors() as $error) {
                $errors[] = $error;
            }

            if ($form->hasChildren()) {
                foreach ($form->getChildren() as $child) {
                    if (!$child->isValid()) {
                        $childErrors = $this->getEntryFormErrors($child);
                        foreach ($childErrors as $childError) {
                            $errors[] = $childError;
                        }
                    }
                }
            }

            return $errors;
        }

        return null;
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\EntryRepository
     */
    private function getEntryRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:SweepstakesEntry')
        ;
    }

    /**
     * @return \Platformd\GroupBundle\Model\GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }

    /**
     * @return \Platformd\CEVOBundle\Api\ApiManager
     */
    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}

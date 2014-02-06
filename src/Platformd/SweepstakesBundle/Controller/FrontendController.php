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
use Platformd\UserBundle\Entity\RegistrationSource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends Controller
{
    public function indexAction()
    {
        $sweepstakess = $this->getSweepstakesRepo()->findPublished($this->getCurrentSite());

        return $this->render('SweepstakesBundle:Frontend:index.html.twig', array(
            'sweepstakess' => $sweepstakess
        ));
    }

    public function showSweepstakesAction($slug, Request $request)
    {
        return $this->show($slug, Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES, $request);
    }

    public function showPromoCodeContestAction($slug, Request $request)
    {
        return $this->show($slug, Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE, $request);
    }

    private function show($slug, $type, Request $request)
    {
        $sweepstakes   = $this->findSweepstakes($slug, false, $type);
        $user          = $this->getCurrentUser();
        $isGroupMember = null;
        $entryFlash    = null;
        $em            = $this->getDoctrine()->getEntityManager();
        $registered    = $request->query->get('registered');
        $timedout      = $request->query->get('timedout');
        $suspended     = $request->query->get('suspended');

        $canTest = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if (!$sweepstakes->getPublished() && !$canTest) {
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

        if ($isEntered && $sweepstakes->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE) {
            $isWinner = $this->getCodeRepo()->findOneBy(array(
                'contest' => $sweepstakes->getId(),
                'user'    => $user->getId(),
            ));

            if ($isWinner) {
                $exposer = $this->container->get('media_exposer');

                $flashMessage = $sweepstakes->getWinnerMessage();
                $flashMessage = str_replace(array(
                    '--contestName--', '--w9Url--', '--affidavitUrl--'
                ), array(
                    $sweepstakes->getName(),
                    $exposer->getPath($sweepstakes->getW9Form()),
                    $exposer->getPath($sweepstakes->getAffidavit())
                ), $flashMessage);

                $entryFlash = array('type' => 'success', 'message' => $flashMessage);
            } else {
                $consolation = $this->getConsolationCodeRepo()->findOneBy(array(
                    'contest' => $sweepstakes->getId(),
                    'user'    => $user->getId(),
                ));

                if ($consolation) {
                    $flashMessage = str_replace('--code--', $consolation->getValue(), $sweepstakes->getLoserMessage());
                    $entryFlash = array('type' => 'info', 'message' => $flashMessage);
                } else {
                    $flashMessage = $sweepstakes->getBackupLoserMessage() ?: $this->trans('platformd.sweepstakes.promo_code.flash.loser_no_code');
                    $entryFlash = array('type' => 'info', 'message' => $flashMessage);
                }
            }
        }

        foreach ($sweepstakes->getQuestions() as $question) {
            $entry->addAnswer(new SweepstakesAnswer($question, $entry));
        }

        $entryForm   = $this->createForm('platformd_sweeps_entry', $entry);
        $formHandler = $this->container->get('platformd_sweeps.entry.form.handler');
        $formHandler->setForm($entryForm);

        if ($sweepstakes->isCurrentlyOpen()) {
            $process = $formHandler->process(true);

            if($process) {
                if (!$this->isGranted('ROLE_USER')) {
                    return $this->redirect($this->generateUrl($sweepstakes->getLinkableRouteName(), array('slug' => $slug, 'registered' => '1')));
                }

                return $this->redirect($this->generateUrl($sweepstakes->getLinkableRouteName(), array('slug' => $slug)));
            }
        }

        return $this->render('SweepstakesBundle:Frontend:show.html.twig', array(
            'sweepstakes'   => $sweepstakes,
            'isEntered'     => $isEntered,
            'isGroupMember' => $isGroupMember,
            'entryForm'     => $entryForm->createView(),
            'errors'        => $this->getEntryFormErrors($entryForm),
            'registered'    => $registered,
            'timedout'      => $timedout,
            'suspended'     => $suspended,
            'entryFlash'    => $entryFlash,
            'rulesRoute'    => ($sweepstakes->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE ? 'promo_code_contest_rules' : 'sweepstakes_rules'),
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_SWEEPSTAKES, 'id'=>$sweepstakes->getId()),
        ));
    }

    public function sweepstakesRulesAction(Request $request, $slug)
    {
        return $this->showRules($request, $slug, Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES);
    }

    public function promoCodeContestRulesAction(Request $request, $slug)
    {
        return $this->showRules($request, $slug, Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE);
    }

    private function showRules(Request $request, $slug, $type)
    {
        $sweepstakes    = $this->findSweepstakes($slug, false, $type);
        $canTest        = $sweepstakes->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));

        if (!$sweepstakes->getPublished() && !$canTest) {
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
    private function findSweepstakes($slug, $restrictUnpublished = true, $type = Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES)
    {
        $sweepstakes = $this->getSweepstakesRepo()->findOneBySlugForSite($slug, $this->getCurrentSite(), $type);

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

    private function getCodeRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:PromoCodeContestCode')
        ;
    }

    private function getConsolationCodeRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:PromoCodeContestConsolationCode')
        ;
    }

    /**
     * @return \Platformd\CEVOBundle\Api\ApiManager
     */
    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}

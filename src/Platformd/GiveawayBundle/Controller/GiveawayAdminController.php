<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Form\Type\GiveawayType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use DateTime;
use Platformd\GiveawayBundle\Model\Exception\MissingKeyException;
use Symfony\Component\Form\FormError;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;
use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\EntityRepository;

class GiveawayAdminController extends Controller
{
    public function indexAction()
    {
        $this->addGiveawayBreadcrumb();
        $giveaways = $this->getGiveawayRepo()->findAllWithoutLocaleOrderedByNewest();

        return $this->render('GiveawayBundle:GiveawayAdmin:index.html.twig',
            array('giveaways' => $giveaways));
    }

    public function newAction(Request $request)
    {
        $this->addGiveawayBreadcrumb()->addChild('New');
        $giveaway = new Giveaway();

        // guarantee we have at least 5 open giveaway boxes
        $this->setupEmptyRedemptionInstructions($giveaway);

        $form = $this->createForm(new GiveawayType(), $giveaway);

        if($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);

            if($form->isValid())
            {
                $this->saveGiveaway($form);

                // redirect to the "new pool" page
                return $this->redirect($this->generateUrl('admin_giveaway_pool_new', array('giveaway' => $giveaway->getId())));
            }
        }

        return $this->render('GiveawayBundle:GiveawayAdmin:new.html.twig', array(
            'form' => $form->createView(),
            'giveaway' => $giveaway,
        ));
    }

    /**
     * Export CSV file of pending machine code entries for this giveaway
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction($id)
    {
        /** @var $giveaway \Platformd\GiveawayBundle\Entity\Giveaway */
        $giveaway = $this->getGiveawayRepo()->find($id);
        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway found for id '.$id);
        }

        $machineCodes = $this->getMachineCodeRepository()->findPendingForGiveaway($giveaway);

        return $this->generateMachineCodeCsvResponse($machineCodes, $giveaway->getSlug());
    }

    public function exportApprovedAndDeniedAction($id)
    {

        $giveaway = $this->getGiveawayRepo()->find($id);

        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway found for id '.$id);
        }

        $machineCodes = $this->getMachineCodeRepository()->findApprovedAndDeniedForGiveaway($giveaway);

        return $this->generateApprovedAndDeniedMachineCodeCsvResponse($machineCodes, $giveaway->getSlug());
    }

    private function generateApprovedAndDeniedMachineCodeCsvResponse(array $machineCodes, $baseFilename)
    {
        // generate CSV content from the rows of data
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
                'First Name',
                'Last Name',
                'Email',
                'Submitted Date',
                'Machine Code',
                'Approval/Denied Action',
                'Approval/Denied Date',
                'Notification Email Sent'
        ));

        foreach ($machineCodes as $entry) {

            $status                         = $entry->getStatus();
            $statusDate                     = $entry->getStatus() == MachineCodeEntry::STATUS_APPROVED ? $entry->getApprovedAt() : $entry->getDeniedAt();
            $statusDateFormatted            = $statusDate ? $statusDate->format('Y-m-d H:i:s') : "-";
            $notificationDate               = $entry->getNotificationEmailSentAt();
            $notificationStatusFormatted    = $notificationDate ? "yes" : "no";

            $factory->addRow(array(
                    $entry->getUser()->getFirstname(),
                    $entry->getUser()->getLastname(),
                    $entry->getUser()->getEmail(),
                    $entry->getCreated()->format('Y-m-d H:i:s'),
                    $entry->getMachineCode(),
                    $status,
                    $statusDateFormatted,
                    $notificationStatusFormatted
            ));
        }

        $filename = sprintf('%s-Denied-Entries-%s.csv', $baseFilename, date('Y-m-d'));

        return $factory->createResponse($filename);
    }

    private function generateMachineCodeCsvResponse(array $machineCodes, $baseFilename)
    {
        // generate CSV content from the rows of data
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
                'First Name',
                'Last Name',
                'Email',
                'Submitted Date',
                'Machine Code',
        ));

        foreach ($machineCodes as $entry) {
            $factory->addRow(array(
                    $entry->getUser()->getFirstname(),
                    $entry->getUser()->getLastname(),
                    $entry->getUser()->getEmail(),
                    $entry->getCreated()->format('Y-m-d H:i:s'),
                    $entry->getMachineCode(),
            ));
        }

        $filename = sprintf('%s-%s.csv', $baseFilename, date('Y-m-d'));

        return $factory->createResponse($filename);
    }

    public function editAction(Request $request, $id)
    {
        $this->addGiveawayBreadcrumb()->addChild('Edit');
        $giveaway = $this->getGiveawayRepo()->findOneById($id);

        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $test   = $giveaway->getTestOnly();
        if ($test === null) {
            $giveaway->setTestOnly(0);
        }

        $this->setupEmptyRedemptionInstructions($giveaway);

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

    /**
     * Allows the user to approve machine codes
     *
     * @Template()
     *
     * @param $id
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function codesAction($id, Request $request)
    {
        $this->addGiveawayBreadcrumb()->addChild('Approve machine codes');

        $giveaway = $this->getGiveawayRepo()->find($id);
        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $form = $this->createFormBuilder()
            ->add('emails', 'textarea', array('attr' => array('class' => 'input-xlarge')))
            ->getForm()
        ;

        $successEmails = array();
        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $emails = explode(',', $data['emails']);

                // iterate through the emails and activate their machine codes
                foreach ($emails as $email) {
                    $user = $this->getUserManager()->findUserByEmail(trim($email));
                    if (!$user) {
                        $form->addError(new FormError('No user with email %email% found', array('%email%' => $email)));

                        continue;
                    }
                    $machineCodes = $this->getMachineCodeRepository()->findPendingUserEntriesForGiveaway($user, $giveaway);

                    if (count($machineCodes) == 0) {
                        $form->addError(new FormError('No submitted code found for email %email%', array('%email%' => $email)));

                        continue;
                    }

                    // pop up the first one, ideally there's only one
                    $machineCode = $machineCodes[0];

                    try {
                        $this->getGiveawayManager()->approveMachineCode($machineCode, $this->getCurrentSite());

                        $successEmails[] = $email;
                    } catch (MissingKeyException $e) {
                        $form->addError(new FormError(
                            'There are no more unassigned giveaway keys for this giveaway. The following email was not assigned a key: %email%',
                            array('%email%' => $email)
                        ));
                    }
                }

                $this->setFlash('success', sprintf('%s codes were approved', count($successEmails)));

                // if the form is *still* valid, redirect
                if ($form->isValid()) {
                    return $this->redirect(
                        $this->generateUrl('admin_giveaway_machine_codes', array('id' => $giveaway->getId()))
                    );
                }
            }
        }

        return array(
            'giveaway' => $giveaway,
            'form'     => $form->createView(),
            'successEmails' => $successEmails,
        );
    }

    /**
     * Allows the user to deny machine codes
     *
     * @Template()
     *
     * @param $id
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function denyCodesAction($id, Request $request)
    {
        $this->addGiveawayBreadcrumb()->addChild('Deny machine codes');

        $giveaway = $this->getGiveawayRepo()->find($id);
        if (!$giveaway) {
            throw $this->createNotFoundException('No giveaway for that id');
        }

        $form = $this->createFormBuilder()
            ->add('emails', 'textarea', array('attr' => array('class' => 'input-xlarge')))
            ->getForm()
        ;

        $successEmails = array();
        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $emails = explode(',', $data['emails']);

                // iterate through the emails and deny their machine codes
                foreach ($emails as $email) {
                    $user = $this->getUserManager()->findUserByEmail(trim($email));
                    if (!$user) {
                        $form->addError(new FormError('No user with email %email% found', array('%email%' => $email)));

                        continue;
                    }

                    $machineCodes = $this->getMachineCodeRepository()->findPendingUserEntriesForGiveaway($user, $giveaway);

                    if (count($machineCodes) == 0) {
                        $form->addError(new FormError('No submitted code found for email %email%', array('%email%' => $email)));

                        continue;
                    }

                    // pop up the first one, ideally there's only one
                    $machineCode = $machineCodes[0];

                    $this->getGiveawayManager()->denyMachineCode($machineCode, $this->getCurrentSite());

                    $successEmails[] = $email;
                }

                $this->setFlash('success', sprintf('%s codes were denied', count($successEmails)));

                return $this->redirect($this->generateUrl('admin_giveaway_machine_deny_codes', array('id' => $giveaway->getId())));
            }
        }

        return array(
            'giveaway' => $giveaway,
            'form'     => $form->createView(),
            'successEmails' => $successEmails,
        );
    }

    /**
     * Shows key giveaway metrics
     * @Template()
     * @return array
     */
    public function metricsAction(Request $request)
    {
        /** @var $metricManager \Platformd\SpoutletBundle\Metric\MetricManager */
        $metricManager = $this->container->get('platformd.metric_manager');

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Giveaways');

        $filterForm = $metricManager->createFilterFormBuilder($this->get('form.factory'))
            ->add('giveaway', 'entity', array(
                'class' => 'GiveawayBundle:Giveaway',
                'property' => 'name',
                'empty_value' => 'All Giveaways',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.name', 'ASC');
                },
            ))
            ->getForm()
        ;

         // default filtering stuff
        $from   = null;
        $to     = null;
        $giveaway   = null;

        $requestData = $request->query->get($filterForm->getName());
        if (!empty($requestData)) {
            $filterForm->bindRequest($request);
            if ($filterForm->isValid()) {
                $data   = $filterForm->getData();

                $from   = $data['startDate'] ? : null;
                $to     = $data['endDate'] ? : null;
                $giveaway   = $data['giveaway'] ? : null;
            }
        }

        if ($giveaway == null) {
            $giveaways  = $this->getGiveawayRepo()->findAllOrderedByNewest();
        } else {
            $giveaways  = $giveaway ? array($giveaway) : $this->getGiveawayRepo()->findAllOrderedByNewest();
        }

        $giveawayMetrics = array();

        foreach($giveaways as $giveaway) {
            $giveawayMetrics[] = $metricManager->createGiveawaysReport($giveaway, $from, $to);
        }

        return array(
            'metrics' => $giveawayMetrics,
            'sites'   => $metricManager->getSites(),
            'form'    => $filterForm->createView()
        );
    }

    protected function retrieveGiveaway($id)
    {
        if (!$giveaway = $this->getGiveawayRepo()->find($id)) {
            throw $this->createNotFoundException();
        }

        return $giveaway;
    }

    private function setupEmptyRedemptionInstructions(Giveaway $giveaway)
    {
        $instructions = $giveaway->getRedemptionInstructionsArray();
        while (count($instructions) < 6) {
            $instructions[] = '';
        }

        $giveaway->setRedemptionInstructionsArray($instructions);
    }

    private function saveGiveaway(Form $giveawayForm)
    {
        // save to db
        $giveaway = $giveawayForm->getData();
        $startsAt = $giveaway->getCreated() === NULL ? new DateTime : $giveaway->getCreated();
        $giveaway->setStartsAt($startsAt);

        $ruleset    = $giveaway->getRuleset();
        $rules      = $ruleset->getRules();

        $newRulesArray = array();

        $defaultAllow = true;

        foreach ($rules as $rule) {
            if ($rule->getMinAge() || $rule->getMaxAge() || $rule->getCountry()) {
                $rule->setRuleset($ruleset);
                $newRulesArray[] = $rule;

                $defaultAllow = $rule->getRuleType() == "allow" ? false : true;
            }
        }

        $oldRules = $this->getEntityManager()->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

        if ($oldRules) {
            foreach ($oldRules as $oldRule) {
                if (!in_array($oldRule, $newRulesArray)) {
                    $oldRule->setRuleset(null);
                }
            }
        }

        $giveaway->getRuleset()->setParentType('giveaway');
        $giveaway->getRuleset()->setDefaultAllow($defaultAllow);

        $this
            ->get('pd_giveaway.giveaway_manager')
            ->save($giveaway);

        $this->setFlash('success', 'platformd.giveaway.admin.saved');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getDoctrine()
            ->getEntityManager();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGiveawayBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Giveaways', array(
            'route' => 'admin_giveaway_index'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\GiveawayBundle\Model\GiveawayManager
     */
    private function getGiveawayManager()
    {
        return $this->container->get('pd_giveaway.giveaway_manager');
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\MachineCodeEntryRepository
     */
    private function getMachineCodeRepository()
    {
        return $this->getEntityManager()->getRepository('GiveawayBundle:MachineCodeEntry');
    }
}

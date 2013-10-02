<?php

namespace Platformd\GiveawayBundle\Command;

use Platformd\SpoutletBundle\Command\BaseCommand,
    Platformd\GiveawayBundle\Entity\CodeAssignment
;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use DateTime;

class CodeAssignmentEmailsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('pd:codeAssignment:sendEmails')
            ->setDescription('Sends emails to users who have been assigned codes in a code assignment.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command sends all outstanding emails to inform users/LAN centers of codes they have been assigned.

  <info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput = $output;
        $container       = $this->getContainer();
        $em              = $container->get('doctrine')->getEntityManager();
        $translator      = $container->get('platformd.model.translator');
        $emailManager    = $container->get('platformd.model.email_manager');
        $codeRepo        = $em->getRepository('GiveawayBundle:CodeAssignmentCode');

        $this->output();
        $this->output(0, 'PlatformD Code Assignment Emailer');

        $this->output();
        $this->output(2, 'Getting list of codes to send...', false);

        $codeCount = $codeRepo->getUnsentCodeCount();

        $this->tick();

        if ($codeCount < 1) {
            $this->output(2, 'No emails.');
            $this->output();

            exit;
        }

        $codesQuery = $codeRepo->getUnsentCodeQuery();

        $iterableResult = $codesQuery->iterate();

        $this->output(2, 'Building email list for '.$codeCount.' codes.');

        $emails          = array();
        $assignmentNames = array();
        $assignmentUrls  = array();

        foreach ($iterableResult as $codeInfo) {
            $codeInfo     = $codeInfo[0];

            $type         = $codeInfo->getAssignment()->getType();
            $assignmentId = $codeInfo->getAssignment()->getId();
            $user         = $codeInfo->getUser();

            if (!isset($assignmentNames[$assignmentId])) {
                $assignmentNames[$assignmentId] = $codeInfo->getAssignment()->getName();
            }

            if (!isset($assignmentUrls[$assignmentId])) {
                $assignmentUrls[$assignmentId] = $codeInfo->getAssignment()->getUrl();
            }

            $emails[$type][$assignmentId][$user->getEmail()][] = array($codeInfo->getCode(), ($user->getLocale() ?: 'en'), $codeInfo);
        }

        $types = CodeAssignment::getValidTypes();

        foreach ($emails as $type => $assignments) {
            $this->output(2, 'Sending emails to '.$types[$type].'.');

            foreach ($assignments as $assignmentId => $emailAddresses) {
                foreach ($emailAddresses as $emailAddress => $codes) {
                    $this->output(4, 'Sending email to '.$emailAddress.' with code(s) for "'.$assignmentNames[$assignmentId].'".');

                    switch ($type) {
                        case CodeAssignment::TYPE_LAN:
                            $liString          = '';
                            $codesToFlagAsSent = array();

                            foreach ($codes as $codeData) {
                                $liString            .= '<li>'.$codeData[0].'</li>';
                                $locale              = $codeData[1];
                                $codesToFlagAsSent[] = $codeData[2];
                            }

                            $emailSubject = $translator->trans('platformd.code_assignment.lan_centers_email.subject', array(
                                '%assignmentName%' => $assignmentNames[$assignmentId],
                            ), 'messages', $locale);

                            $emailContent = $translator->trans('platformd.code_assignment.lan_centers_email.content', array(
                                '%assignmentName%' => $assignmentNames[$assignmentId],
                                '%codes%'          => $liString,
                                '%assignmentUrl%'  => $assignmentUrls[$assignmentId],
                            ), 'messages', $locale);

                            $emailManager->sendHtmlEmail($emailAddress, $emailSubject, $emailContent, "Code Assignment Email", $locale);

                            foreach ($codesToFlagAsSent as $codeToFlag) {
                                $codeToFlag->setEmailSentAt(new DateTime());
                                $em->persist($codeToFlag);
                            }

                            $em->flush();

                            break;

                        case CodeAssignment::TYPE_USERS:

                            foreach ($codes as $codeData) {
                                $code        = $codeData[0];
                                $locale      = $codeData[1];
                                $codesEntity = $codeData[2];

                                $emailSubject = $translator->trans('platformd.code_assignment.users_email.subject', array(
                                    '%assignmentName%' => $assignmentNames[$assignmentId],
                                ), 'messages', $locale);

                                $emailContent = $translator->trans('platformd.code_assignment.users_email.content', array(
                                    '%assignmentUrl%'  => $assignmentUrls[$assignmentId],
                                    '%assignmentName%' => $assignmentNames[$assignmentId],
                                    '%code%'           => $code,
                                ), 'messages', $locale);

                                $emailManager->sendHtmlEmail($emailAddress, $emailSubject, $emailContent, "Code Assignment Email", $locale);

                                $codesEntity->setEmailSentAt(new DateTime());
                                $em->persist($codesEntity);
                            }

                            $em->flush();

                            break;

                        default:
                            break;
                    }
                }

            }
        }

        $this->output();
        $this->output(2, 'Done.');

        $this->outputErrors();

        $this->output(0);
    }
}

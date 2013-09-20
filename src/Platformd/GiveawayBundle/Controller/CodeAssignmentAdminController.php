<?php

namespace Platformd\GiveawayBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\GiveawayBundle\Entity\CodeAssignment;
use Platformd\GiveawayBundle\Entity\CodeAssignmentCode;
use Platformd\GiveawayBundle\Form\Type\CodeAssignmentType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

class CodeAssignmentAdminController extends Controller
{
    public function indexAction(Request $request)
    {
        $this->getBreadcrumbs()->addChild('Assign Codes');

        $assignment = new CodeAssignment();
        $form       = $this->createForm(new CodeAssignmentType(), $assignment);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $em         = $this->getDoctrine()->getEntityManager();
                $assignment = $form->getData();

                if (null === $assignment->getCodesFile()) {
                    $this->setFlash('error', 'Please attach a .csv file containing the codes to be assigned.');
                    return $this->redirect($this->generateUrl('admin_assign_codes'));
                }

                $em->persist($assignment);
                $em->flush();

                try {
                    $assignmentData = $this->loadCodesFromFile($assignment->getCodesFile(), $assignment);
                } catch (Exception $e) {
                    $this->setFlash('error', $e->getMessage());
                    return $this->redirect($this->generateUrl('admin_assign_codes'));
                }

                $errorString = '';
                $hasErrors   = false;
                $errorCount  = 0;

                if (count($assignmentData['codesWithoutEmail']) > 0) {
                    $hasErrors  = true;
                    $errorCount += count($assignmentData['codesWithoutEmail']);

                    $errorString .= '<br>The following codes did not have a corresponding email address:<br><ul>';

                    foreach ($assignmentData['codesWithoutEmail'] as $code) {
                        $errorString .= '<li>'.$code.'</li>';
                    }

                    $errorString .= '</ul>';
                }

                if (count($assignmentData['emailsWithoutCode']) > 0) {
                    $hasErrors = true;
                    $errorCount += count($assignmentData['emailsWithoutCode']);

                    $errorString .= '<br>The following email addresses did not have a corresponding code:<br><ul>';

                    foreach ($assignmentData['emailsWithoutCode'] as $email) {
                        $errorString .= '<li>'.$email.'</li>';
                    }

                    $errorString .= '</ul>';
                }

                if (count($assignmentData['invalidUsers']) > 0) {
                    $hasErrors = true;
                    $errorCount += count($assignmentData['invalidUsers']);

                    $errorString .= '<br>The following email addresses did not match a known user:<br><ul>';

                    foreach ($assignmentData['invalidUsers'] as $email) {
                        $errorString .= '<li>'.$email.'</li>';
                    }

                    $errorString .= '</ul>';
                }

                if ($hasErrors) {
                    $pluralAssigned = $assignmentData['assignedCount'] !== 1;
                    $pluralError    = $errorCount !== 1;

                    $this->setFlash('info', $assignmentData['assignedCount'].' code'.($pluralAssigned ? 's have' : ' has').' been assigned, however '.$errorCount.' code'.($pluralError ? 's' : '').'/account'.($pluralError ? 's were' : ' was').' not assigned:<br>'.$errorString);
                    return $this->redirect($this->generateUrl('admin_assign_codes'));
                }

                $this->setFlash('success', 'All '.$assignmentData['assignedCount'].' codes have been assigned.');
                return $this->redirect($this->generateUrl('admin_assign_codes'));
            }
        }

        return $this->render('GiveawayBundle:CodeAssignment:index.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function loadCodesFromFile(File $file, $assignment)
    {
        $batchCount        = 250;
        $i                 = 0;
        $emails            = array();
        $codeInfo          = array();
        $codesWithoutEmail = array();
        $emailsWithoutCode = array();
        $formattedCodes    = array();
        $formatString      = '(%s, %s, '.$assignment->getId().')';

        $isUserAssignment      = $assignment->getType() == CodeAssignment::TYPE_USERS;
        $isLanCenterAssignment = $assignment->getType() == CodeAssignment::TYPE_LAN;

        $openFile  = $file->openFile();
        $lastEmail = null;

        while (!$openFile->eof()) {

            $csvRow = $openFile->fgetcsv();
            $email  = null;

            if (!$csvRow || empty($csvRow) || (!$csvRow[0] && !isset($csvRow[1]))) {
                continue;
            }

            if(!$csvRow[0] && isset($csvRow[1])) {

                if ($isUserAssignment || !$lastEmail) {
                    $codesWithoutEmail[] = $csvRow[1];
                    continue;
                }

                $email = $lastEmail;
            }

            if((!isset($csvRow[1]) || empty($csvRow[1])) && null !== $csvRow[0]) {
                $emailsWithoutCode[] = $csvRow[0];
                continue;
            }

            $email = $email ?: $csvRow[0];

            $emails[]         = $email;
            $lastEmail        = $email;
            $codeInfo[$email][] = $csvRow[1];
        }

        $em    = $this->getDoctrine()->getEntityManager();
        $usersQuery = $em->getRepository('UserBundle:User')->getFindUserListByEmailQuery($emails);

        $iterableResult = $usersQuery->iterate();

        $userArr = array();

        foreach ($iterableResult as $user) {
            $userArr[$user[0]->getEmail()] = $user[0]->getId();
        }

        $invalidUsers  = array();
        $assignedCount = 0;

        $em         = $this->getDoctrine()->getEntityManager();
        $connection = $em->getConnection();

        foreach ($codeInfo as $email => $codes) {
            foreach ($codes as $code) {
                if (isset($userArr[$email])) {
                    $formattedCodes[] = sprintf($formatString, $userArr[$email], $connection->quote(trim($code)));
                    $assignedCount++;
                    $i++;
                } else {
                    $invalidUsers[] = $email;
                    continue;
                }

                if ($i >= $batchCount) {
                    $this->executeLoadQuery($formattedCodes, $connection);
                    $formattedCodes = array();
                    $i = 0;
                }
            }
        }

        if (!empty($formattedCodes)) {
            $this->executeLoadQuery($formattedCodes, $connection);
        }

        $returnData = array('assignedCount' => $assignedCount, 'codesWithoutEmail' => $codesWithoutEmail, 'emailsWithoutCode' => $emailsWithoutCode, 'invalidUsers' => $invalidUsers);

        return $returnData;
    }

    private function executeLoadQuery(array $valuesString, $connection)
    {
        if (empty($valuesString)) {
            return;
        }

        $query = sprintf('INSERT INTO `code_assignment_code` (user, code, assignment) VALUES %s', implode(', ', $valuesString));
        $stmt = $connection->prepare($query);

        $stmt->execute();
    }
}

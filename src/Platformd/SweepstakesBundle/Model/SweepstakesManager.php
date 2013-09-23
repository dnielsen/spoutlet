<?php

namespace Platformd\SweepstakesBundle\Model;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\PromoCodeContestCode;
use Platformd\SweepstakesBundle\Entity\PromoCodeContestConsolationCode;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ORM\EntityManager;
use Knp\MediaBundle\Util\MediaUtil;

class SweepstakesManager
{
    private $em;
    private $tagManager;
    private $flashUtil;

    public function __construct(EntityManager $em, $tagManager, $flashUtil)
    {
        $this->em         = $em;
        $this->tagManager = $tagManager;
        $this->flashUtil  = $flashUtil;
    }

    public function saveSweepstakes(Form $sweepstakesForm, $originalQuestions = array())
    {
        $sweepstakes = $sweepstakesForm->getData();

        $tags = $this->tagManager->loadOrCreateTags($this->tagManager->splitTagNames($sweepstakesForm['tags']->getData()));

        $sweepstakes->getId() ? $this->tagManager->replaceTags($tags, $sweepstakes) : $this->tagManager->addTags($tags, $sweepstakes);

        $mUtil = new MediaUtil($this->em);

        if (!$mUtil->persistRelatedMedia($sweepstakes->getBackgroundImage())) {
            $sweepstakes->setBackgroundImage(null);
        }

        if (!$sweepstakes->getHasOptionalCheckbox()) {
            $sweepstakes->setOptionalCheckboxLabel(null);
        } else {
            $checkboxText = $sweepstakes->getOptionalCheckboxLabel();
            if (empty($checkboxText)) {
                $sweepstakes->setHasOptionalCheckbox(false);
            }
        }

        if ($sweepstakes->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES) {

            $groupId = $sweepstakesForm['group']->getData();

            if($groupId) {
                $group = $this->em->getRepository('GroupBundle:Group')->find($groupId);

                if($group) {
                    $sweepstakes->setGroup($group);
                }
            }

            foreach ($sweepstakes->getQuestions() as $question) {
                foreach ($originalQuestions as $key => $toDel) {
                    if ($toDel->getId() === $question->getId()) {
                        unset($originalQuestions[$key]);
                    }
                }
            }

            // remove the relationship between the question and the sweepstakes
            foreach ($originalQuestions as $question) {
                $em->remove($question);
            }

            $this->em->persist($sweepstakes);
            $this->em->flush();
        } else {
            if (!$mUtil->persistRelatedMedia($sweepstakes->getAffidavit())) {
                $sweepstakes->setAffidavit(null);
            }

            if (!$mUtil->persistRelatedMedia($sweepstakes->getW9Form())) {
                $sweepstakes->setW9Form(null);
            }

            // flushing before loading keys
            $this->em->persist($sweepstakes);
            $this->em->flush();

            $winningCodesFile     = $sweepstakes->getWinningCodesFile();
            $consolationCodesFile = $sweepstakes->getConsolationCodesFile();

            if ($winningCodesFile) {
                $this->loadWinningCodesFromFile($winningCodesFile, $sweepstakes);
            }

            if ($consolationCodesFile) {
                $this->loadConsolationCodesFromFile($consolationCodesFile, $sweepstakes);
            }
        }

        $this->tagManager->saveTagging($sweepstakes);
        $this->tagManager->loadTagging($sweepstakes);

        $this->flashUtil->setFlash('success', 'Sweepstakes Saved');
    }

    public function loadWinningCodesFromFile(File $file, $sweepstakes)
    {
        return $this->loadCodesFromFile($file, $sweepstakes, true);
    }

    public function loadConsolationCodesFromFile(File $file, $sweepstakes)
    {
        return $this->loadCodesFromFile($file, $sweepstakes, false);
    }

    private function loadCodesFromFile(File $file, $sweepstakes, $areCodesWinning=true)
    {
        $batchCount    = 250;
        $i             = 0;
        $openFile      = $file->openFile();
        $formatString  = '(%s, '.$sweepstakes->getId().')';
        $formattedKeys = array();
        $connection    = $this->em->getConnection();

        while (!$openFile->eof()) {

            $csvRow = $openFile->fgetcsv();

            if (!$csvRow || empty($csvRow) || empty($csvRow[0])) {
                continue;
            }

            $formattedKeys[] = sprintf($formatString, $connection->quote(trim($csvRow[0])));

            $i++;

            if ($i >= $batchCount) {
                $this->executeLoadQuery($formattedKeys, $areCodesWinning);
                $formattedKeys = array();

                if ($areCodesWinning) {
                    $sweepstakes->incrementWinningCodesCount($i);
                } else {
                    $sweepstakes->incrementConsolationCodesCount($i);
                }

                $i = 0;
            }
        }

        if (!empty($formattedKeys)) {
            $this->executeLoadQuery($formattedKeys, $areCodesWinning);
            if ($areCodesWinning) {
                $sweepstakes->incrementWinningCodesCount($i);
            } else {
                $sweepstakes->incrementConsolationCodesCount($i);
            }
        }

        $this->em->persist($sweepstakes);
        $this->em->flush();
    }

    private function executeLoadQuery(array $valuesString, $areCodesWinning)
    {
        if (empty($valuesString)) {
            return;
        }

        $connection = $this->em->getConnection();
        $tableName  = $areCodesWinning ? 'promo_code_contest_winning_code' : 'promo_code_contest_consolation_code';

        $query = sprintf('INSERT INTO `%s` (value, contest_id) VALUES %s', $tableName, implode(', ', $valuesString));
        $stmt = $connection->prepare($query);

        $stmt->execute();
    }

    public function getCodeCount($sweepstakes, $areCodesWinning)
    {
        return $this->em->getRepository('SweepstakesBundle:'.($areCodesWinning ? 'PromoCodeContestCode' : 'PromoCodeContestConsolationCode'))->findCountForSweepstakes($sweepstakes);
    }
}

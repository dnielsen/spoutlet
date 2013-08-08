<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;

class ExportQueryManager
{
    const REPORT_USER_OPTIN = 'report_user_optin';

    static private $reportTypes = array(
        self::REPORT_USER_OPTIN,
    );

    protected $em;
    protected $csv;

    public function __construct(EntityManager $entityManager, CsvResponseFactory $csv)
    {
        $this->em   = $entityManager;
        $this->csv  = $csv;
    }

    public function getReport($reportType, array $options)
    {
        switch ($reportType) {
            case self::REPORT_USER_OPTIN:
                return $this->getUserOptIns($options['fromDate'], $options['thruDate'], $options['sites']);

            default:
                return false;
        }
    }

    private function getUserOptIns($fromDate, $thruDate, $sites)
    {
        $query   = $this->em->getRepository('UserBundle:User')->getOptedInUserQuery($fromDate, $thruDate, $sites);
        $results = $query->iterate();

        $this->csv->addRow(array(
            'User ID',
            'Username',
            'Email',
            'Creation Date',
            'Last Seen',
            'First Name',
            'Last Name',
            'State/Province',
            'Country'
        ));

        foreach ($results as $result) {
            # build csv file

            $lastLogin = $result[0]->getLastLogin() ? $result[0]->getLastLogin()->format('d/m/Y') : '';
            $created   = $result[0]->getCreated() ? $result[0]->getCreated()->format('d/m/Y') : '';

            $this->csv->addRow(array(
                $result[0]->getId(),
                $result[0]->getUsername(),
                $result[0]->getEmail(),
                $created,
                $lastLogin,
                $result[0]->getFirstname(),
                $result[0]->getLastname(),
                $result[0]->getState(),
                $result[0]->getCountry()
            ));

            $this->em->detach($result[0]);
        }

        return $this->csv->createResponse('optin_report.csv');
    }

    public function getReportTypes()
    {
        return self::$reportTypes;
    }
}

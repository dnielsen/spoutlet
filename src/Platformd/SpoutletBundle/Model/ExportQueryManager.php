<?php

namespace Platformd\SpoutletBundle\Model;

use Platformd\UserBundle\Entity\UserManager;

class ExportQueryManager
{
    public const REPORT_USER_OPTIN = 'report_user_optin';

    protected $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function getUserOptIns($fromDate, $thruDate)
    {
        return $this->userManager->getOptedInUsers($fromDate, $thruDate);
    }
}

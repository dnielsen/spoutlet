<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;

use Platformd\SpoutletBundle\Util\IpLookupUtil,
    Platformd\SpoutletBundle\Entity\LoginRecord,
    Platformd\SpoutletBundle\Util\SiteUtil;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\HttpFoundation\Request;

class LoginRecordManager
{
    private $em;
    private $ipUtil;
    private $siteUtil;

    public function __construct(EntityManager $em, IpLookupUtil $ipUtil, SiteUtil $siteUtil)
    {
        $this->em = $em;
        $this->ipUtil = $ipUtil;
        $this->siteUtil = $siteUtil;
    }

    public function recordLogin(TokenInterface $token, Request $request)
    {
        $record         = new LoginRecord();
        $ipAddress      = $request->getClientIp(true);
        $countryCode    = $this->ipUtil->getCountryCode($ipAddress);
        $country        = $this->em->getRepository('SpoutletBundle:Country')->findOneByCode($countryCode);
        $site = $this->siteUtil->getCurrentSite();

        $record->setUser($token->getUser());
        $record->setIpAddress($ipAddress);
        $record->setCountry($country);
        $record->setSite($site);

        $this->em->persist($record);
        $this->em->flush();
    }
}

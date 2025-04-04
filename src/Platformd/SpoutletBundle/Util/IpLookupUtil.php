<?php

namespace Platformd\SpoutletBundle\Util;
use Platformd\SpoutletBundle\Location\Ip2Location;
use Symfony\Component\HttpFoundation\Request;


class IpLookupUtil
{
    private $lookupFile;
    private $testCountryCode = null;

    public function __construct($lookupDir, $override)
    {
        $this->lookupFile = $lookupDir.'ipLookup.bin';

        if ($override) {
            $overrideFile = $lookupDir.'overrideCountry';
            $this->testCountryCode = file_exists($overrideFile) ? trim(file_get_contents($overrideFile)) ?: "US" : "US";
        }
    }

    public function getAll($ipAddress)
    {
        $ip = new Ip2Location();
        $ip->open($this->lookupFile);
        $result = $ip->getAll($ipAddress);

        return $result;
    }

    public function getCountryName($ipAddress)
    {
        return $this->get('countryLong', $ipAddress);
    }

    public function getCountryCode($ipAddress)
    {
        return $this->testCountryCode ?: $this->get('countryShort', $ipAddress);
    }

    public function getRegion($ipAddress)
    {
        return $this->get('region', $ipAddress);
    }

    public function getCity($ipAddress)
    {
        return $this->get('city', $ipAddress);
    }

    private function get($parameter, $ipAddress)
    {
        $ip = new Ip2Location();
        $method = 'get'.ucfirst($parameter);

        if (method_exists($ip, $method) && $this->isIPv4($ipAddress)) {
            $ip->open($this->lookupFile);
            $result = $ip->$method($ipAddress);

            return $result;
        }

        return false;
    }

    public function getClientIp(Request $request)
    {
        return $request->getClientIp(true);
    }

    private function isIPv4($ip){
        return (long2ip(ip2long($ip)) == $ip) ? true : false;
    }
}

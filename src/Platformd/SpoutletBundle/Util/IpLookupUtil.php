<?php

namespace Platformd\SpoutletBundle\Util;

use Symfony\Component\HttpFoundation\Request;


class IpLookupUtil
{
    private $lookupFile;
    private $testCountryCode = null;

    public function __construct($lookupDir, $override)
    {
        $this->lookupFile = $lookupDir.'GeoIP.dat';

        if ($override) {
            $overrideFile = $lookupDir.'overrideCountry';
            $this->testCountryCode = file_exists($overrideFile) ? trim(file_get_contents($overrideFile)) ?: "US" : "US";
        }
    }

    public function getCountryName($ipAddress)
    {
        return $this->get('geoip_country_name_by_addr', $ipAddress);
    }

    public function getCountryCode($ipAddress)
    {
        $result = $this->testCountryCode ?: $this->get('geoip_country_code_by_addr', $ipAddress);

        if (empty($result)) {
            return 'US';
        }

        return $result == 'GB' ? 'UK' : $result;
    }

    private function get($method, $ipAddress)
    {
        if ($this->isIPv4($ipAddress)) {

            $gi = geoip_open($this->lookupFile, GEOIP_STANDARD);
            return $method($gi, $ipAddress);
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

<?php

namespace Platformd\SpoutletBundle\Location;

class Ip2LocationRecord
{
    private $countryShort;
    private $countryLong;
    private $region;
    private $city;
    private $isp;
    private $latitude;
    private $longitude;
    private $domain;
    private $zipCode;
    private $timeZone;
    private $netSpeed;
    private $iddCode;
    private $areaCode;
    private $weatherStationCode;
    private $weatherStationName;
    private $mcc;
    private $mnc;
    private $mobileBrand;
    private $ipAddress;
    private $ipNumber;

    public function getCountryShort() { return $this->countryShort; }
    public function getCountryLong() { return $this->countryLong; }
    public function getRegion() { return $this->region; }
    public function getCity() { return $this->city; }
    public function getIsp() { return $this->isp; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getDomain() { return $this->domain; }
    public function getZipCode() { return $this->zipCode; }
    public function getTimeZone() { return $this->timeZone; }
    public function getNetSpeed() { return $this->netSpeed; }
    public function getIddCode() { return $this->iddCode; }
    public function getAreaCode() { return $this->areaCode; }
    public function getWeatherStationCode() { return $this->weatherStationCode; }
    public function getWeatherStationName() { return $this->weatherStationName; }
    public function getMcc() { return $this->mcc; }
    public function getMnc() { return $this->mnc; }
    public function getMobileBrand() { return $this->mobileBrand; }
    public function getIpAddress() { return $this->ipAddress; }
    public function getIpNumber() { return $this->ipNumber; }
    public function setCountryShort($x) { $this->countryShort = $x; }
    public function setCountryLong($x) { $this->countryLong = $x; }
    public function setRegion($x) { $this->region = $x; }
    public function setCity($x) { $this->city = $x; }
    public function setIsp($x) { $this->isp = $x; }
    public function setLatitude($x) { $this->latitude = $x; }
    public function setLongitude($x) { $this->longitude = $x; }
    public function setDomain($x) { $this->domain = $x; }
    public function setZipCode($x) { $this->zipCode = $x; }
    public function setTimeZone($x) { $this->timeZone = $x; }
    public function setNetSpeed($x) { $this->netSpeed = $x; }
    public function setIddCode($x) { $this->iddCode = $x; }
    public function setAreaCode($x) { $this->areaCode = $x; }
    public function setWeatherStationCode($x) { $this->weatherStationCode = $x; }
    public function setWeatherStationName($x) { $this->weatherStationName = $x; }
    public function setMcc($x) { $this->mcc = $x; }
    public function setMnc($x) { $this->mnc = $x; }
    public function setMobileBrand($x) { $this->mobileBrand = $x; }
    public function setIpAddress($x) { $this->ipAddress = $x; }
    public function setIpNumber($x) { $this->ipNumber = $x; }
}

<?php

namespace Platformd\SweepstakesBundle\Tests\Entity;

use PHPUnit_Framework_TestCase;
use DateTime;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;

class SweepstakesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDates
     * @param \DateTime $birthday
     * @param \DateTime $eventDate
     * @param $oldEnough
     */
    public function testIsUserOldEnough(DateTime $birthday, DateTime $eventDate, $oldEnough)
    {
        $sweepstakes = new Sweepstakes();
        $sweepstakes->setStartsAt($eventDate);
        $sweepstakes->setMinimumAgeRequirement(13);

        $this->assertEquals($oldEnough, $sweepstakes->isUserOldEnough($birthday));
    }

    public function getDates()
    {
        return array(
            array(new DateTime('June 5th 1990'), new DateTime('June 6th 2003'), true),
            array(new DateTime('June 5th 1990'), new DateTime('June 4th 2003'), false),
        );
    }

    public function testIsCountryAllowed()
    {
        $sweepstakes = new Sweepstakes();
        $sweepstakes->setDisallowedCountries(array('GB'));

        $this->assertTrue($sweepstakes->isCountryAllowed('US'));
        $this->assertTrue($sweepstakes->isCountryAllowed('us'));
        $this->assertFalse($sweepstakes->isCountryAllowed('GB'));
        $this->assertFalse($sweepstakes->isCountryAllowed('gb'));
    }

    public function testIsCurrentlyOpen()
    {
        $sweepstakes = new Sweepstakes();

        $start = new DateTime('yesterday');
        $end = new DateTime('tomorrow');
        $sweepstakes->setStartsAt($start);
        $sweepstakes->setEndsAt($end);

        $this->assertTrue($sweepstakes->isCurrentlyOpen());

        $sweepstakes->setEndsAt(new DateTime('yesterday'));
        $this->assertFalse($sweepstakes->isCurrentlyOpen());

        $sweepstakes->setStartsAt(new DateTime('tomorrow'));
        $sweepstakes->setEndsAt(new DateTime('tomorrow'));
        $this->assertFalse($sweepstakes->isCurrentlyOpen());
    }
}
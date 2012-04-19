<?php

namespace Platformd\GiveawayBundle\Test\Entity;

use Platformd\GiveawayBundle\Entity\Giveaway;

class GiveawayTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetRedemptionArray()
    {
        $giveaway = new Giveaway();
        // we always have 6 steps, but they're blank
        $this->assertEquals(array('' ,'', '', '', '', ''), $giveaway->getRedemptionInstructionsArray());
        $this->assertEquals(array(), $giveaway->getCleanedRedemptionInstructionsArray());

        // the simple case
        $arr = array('Foo', 'Bar', '', '', '', '');
        $targetStr = <<< EOF
* Foo
* Bar
EOF;

        $giveaway->setRedemptionInstructionsArray($arr);
        $this->assertEquals($arr, $giveaway->getRedemptionInstructionsArray());
        $this->assertEquals($targetStr, $giveaway->getRedemptionInstructions());

        // now add a mult-line entry
        $multilineEntry = <<< EOF
This is my

multiline title
EOF;

        $arr = array('Foo', $multilineEntry, '', '', '', '');
        $targetStr = <<< EOF
* Foo
* This is my

multiline title
EOF;

        $giveaway->setRedemptionInstructionsArray($arr);
        $this->assertEquals($arr, $giveaway->getRedemptionInstructionsArray());
        $this->assertEquals($targetStr, $giveaway->getRedemptionInstructions());

        // test an empty array - it should not store empty string
        $giveaway->setRedemptionInstructionsArray(array('', '', '', ''));
        $this->assertEquals(array('', '', '', '', '', ''), $giveaway->getRedemptionInstructionsArray());
    }
}
<?php
namespace Platformd\GiveawayBundle\Test\Util;

use Platformd\GiveawayBundle\Util\KeyCounterUtil;

/**
 * @author Ryan Weaver <ryan@knplabs.com>
 */
class KeyCounterUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * See the actual class for how this should all work
     *
     * @dataProvider provideIterations
     *
     * @param $totalKeys
     * @param $trueKeysRemaining
     * @param $lowerLimit
     * @param $upperLimit
     * @param $expected
     */
    public function testGetTrueDisplayCount($totalKeys, $trueKeysRemaining, $lowerLimit, $upperLimit, $expected)
    {
        $counter = new KeyCounterUtil();

        $result = $counter->getTrueDisplayCount($totalKeys, $trueKeysRemaining, $lowerLimit, $upperLimit);

        $this->assertEquals($expected, $result);
    }

    /**
     * Provides the
     *
     * @return array
     */
    public function provideIterations()
    {
        return array(
            // cases with zero'ed limits
            array(100, 100, 0, 0, 100),
            array(100, 100, 0, 1, 100),
            array(100, 100, 1, 0, 100),

            // invalid limits
            array(100, 100, 10, 2, 100),

            // we have less remaining than our upper limit
            array(100, 100, 10, 1000, 100),

            // we have all 5000 left, which is above our limit - should be 1000
            array(5000, 5000, 100, 1000, 1000),
            // now we've given 100 away, should be 900
            array(5000, 4900, 100, 1000, 900),
            // now we've given 899 away, should be 101 (our lower limit)
            array(5000, 4101, 100, 1000, 101),
            // now we've given 900 away, should be 1000 again
            array(5000, 4100, 100, 1000, 1000),

            // now we've given 1799 away, should be 101 (our lower limit)
            array(5000, 3201, 100, 1000, 101),
            // now we've given 1800 away, should be 1000 again
            array(5000, 3200, 100, 1000, 1000),

            // 5000, 4100, 3200, 2300, 1400, 500
            // now we've given 4500 away, *would* be 1000 again, but we only have 500 let
            array(5000, 500, 100, 1000, 500),
            // now it just keeps going down
            array(5000, 200, 100, 1000, 200),
            array(5000, 1, 100, 1000, 1),
            array(5000, 0, 100, 1000, 0),

            // situation where when we hit our lower limit, we have more, but less than our upper limit
            // still haven't quite hit it yet
            array(1000, 701, 500, 800, 501),
            // now we hit it - just show what we DO have left
            array(1000, 700, 500, 800, 700),
        );
    }
}

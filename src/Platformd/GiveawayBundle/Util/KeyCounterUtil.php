<?php

namespace Platformd\GiveawayBundle\Util;

/**
 * Simple class that helps with the logic of how many keys to display:
 *
 * Its kind of a strange requirement .. If a giveaway includes 50,000 keys, they may only want to
 * show 10,000, then when it gets down to 1000 they will reset it to 10,000 until all 50,000 are
 * given away. The 10,000 is the upper limit and the 1000 is the lower limit. I know, strange!
 */
class KeyCounterUtil
{
    public function getTrueDisplayCount($totalKeys, $remainingKeys, $lowerLimit, $upperLimit)
    {
        // both really need to be set for this to work
        if ($lowerLimit < 0 || $upperLimit <= 0) {
            return $remainingKeys;
        }

        // come on - this wouldn't make sense! Just return the true remaining
        if ($lowerLimit >= $upperLimit) {
            return $remainingKeys;
        }

        // if we never had as many as the upper limit, then just return what we have
        if ($totalKeys < $upperLimit) {
            return $remainingKeys;
        }

        $appearanceNumber = $upperLimit;
        $totalGone        = $totalKeys - $remainingKeys;

        for ($i = 0; $i < $totalGone; $i++) {
            $appearanceNumber--;

            // if we've gone below our lower limit and we have more remaining keys
            /*
             * If:
             *  a) we've hit our lower limit
             * AND
             *  b) we have more remaining keys than we're displaying
             * THEN
             *  Show more keys, crazy!
             */
            if ($appearanceNumber <= $lowerLimit && $remainingKeys > $appearanceNumber) {

                // the appearance number is the upper limit, unless there aren't that many remaining anymore
                $appearanceNumber = ($remainingKeys >= $upperLimit) ? $upperLimit : $remainingKeys;
            }
        }

        return $appearanceNumber;
    }
}

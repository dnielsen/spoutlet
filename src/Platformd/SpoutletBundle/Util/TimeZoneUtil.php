<?php

namespace Platformd\SpoutletBundle\Util;

class TimeZoneUtil
{
    const DATE_FORMAT_NO_TZ   = 'Y-m-d H:i:s';
    const DATE_FORMAT_WITH_TZ = 'c';

    /**
     * @return \DateTime
     */
    static public function getDtMergedWithTz(\DateTime $dt, \DateTimeZone $tz)
    {
        return new \DateTime($dt->format(self::DATE_FORMAT_NO_TZ), $tz);
    }

    /**
     * @return \DateTime
     */
    static public function getUtc(\DateTime $localDt, \DateTimeZone $localTz)
    {
        return self::getDtMergedWithTz($localDt, $localTz)->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * @return \DateTime
     */
    static public function getUtcNow()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return DateTime
     */
    static public function getLocal(\DateTime $utcDt, \DateTimeZone $localTz)
    {
        return self::getDtMergedWithTz($utcDt, new \DateTimeZone('UTC'))->setTimezone($localTz);
    }

    static public function isNowBetween($start, $end, \DateTimeZone $startTz = null, \DateTimeZone $endTz = null)
    {
        if (is_null($start) && is_null($end)) { # assuming null = +/-infinity
            return true;
        }

        if (is_null($startTz)) {
            $startTz = new \DateTimeZone('UTC');
        }

        if (is_null($endTz)) {
            $endTz = $startTz;
        }

        $nowUtc = self::getUtcNow();

        $nowBeforeStart = $start  && $nowUtc < self::getUtc($start, $startTz);
        $nowAfterEnd    = $end    && $nowUtc > self::getUtc($end, $endTz);

        return !$nowBeforeStart && !$nowAfterEnd;
    }
}

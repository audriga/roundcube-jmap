<?php

namespace OpenXPort\Util;

use OpenXPort\Jmap\Calendar\NDay;

class RoundcubeCalendarEventAdapterUtil
{
    protected static $logger;

    // Below functions are for iCal -> JMAP value conversion

    public static function convertFromICalFreqToJmapFrequency($freq)
    {
        if (is_null($freq)) {
            return null;
        }

        $jmapFrequency = null;

        switch ($freq) {
            case 'YEARLY':
                $jmapFrequency = 'yearly';
                break;

            case 'MONTHLY':
                $jmapFrequency = 'monthly';
                break;

            case 'WEEKLY':
                $jmapFrequency = 'weekly';
                break;

            case 'DAILY':
                $jmapFrequency = 'daily';
                break;

            case 'HOURLY':
                $jmapFrequency = 'hourly';
                break;

            case 'MINUTELY':
                $jmapFrequency = 'minutely';
                break;

            case 'SECONDLY':
                $jmapFrequency = 'secondly';
                break;

            default:
                $jmapFrequency = null;
                break;
        }

        return $jmapFrequency;
    }

    public static function convertFromICalIntervalToJmapInterval($interval)
    {
        if (is_null($interval)) {
            // 1 is the default JMAP value for interval, that's why set to 1 if input is NULL
            return 1;
        }

        return $interval;
    }

    public static function convertFromICalRScaleToJmapRScale($rscale)
    {
        if (is_null($rscale)) {
            return null;
        }

        // JMAP rscale is essentially iCal rscale, but simply in lowercase. Just return lowercased version of the input
        return strtolower($rscale);
    }

    public static function convertFromICalSkipToJmapSkip($skip)
    {
        if (is_null($skip)) {
            return null;
        }

        $jmapSkip = null;

        switch ($skip) {
            case 'OMIT':
                $jmapSkip = 'omit';
                break;

            case 'BACKWARD':
                $jmapSkip = 'backward';
                break;

            case 'FORWARD':
                $jmapSkip = 'forward';
                break;

            default:
                $jmapSkip = null;
                break;
        }

        return $jmapSkip;
    }

    public static function convertFromICalWKSTToJmapFirstDayOfWeek($wkst)
    {
        if (is_null($wkst)) {
            return null;
        }

        $jmapFirstDayOfWeek = null;

        switch ($wkst) {
            case 'MO':
                $jmapFirstDayOfWeek = 'mo';
                break;

            case 'TU':
                $jmapFirstDayOfWeek = 'tu';
                break;

            case 'WE':
                $jmapFirstDayOfWeek = 'we';
                break;

            case 'TH':
                $jmapFirstDayOfWeek = 'th';
                break;

            case 'FR':
                $jmapFirstDayOfWeek = 'fr';
                break;

            case 'SA':
                $jmapFirstDayOfWeek = 'sa';
                break;

            case 'SU':
                $jmapFirstDayOfWeek = 'su';
                break;

            default:
                $jmapFirstDayOfWeek = null;
                break;
        }

        return $jmapFirstDayOfWeek;
    }

    public static function convertFromICalByDayToJmapByDay($byDay)
    {
        if (is_null($byDay)) {
            return null;
        }

        $splitByDayArray = explode(",", $byDay);

        $jmapByDay = [];

        foreach ($splitByDayArray as $bd) {
            // Parse the BYDAY string from iCal below

            $byDayWeekDayString = null;
            $byDayWeekNumberString = null;

            // Check if we have numeric characters and if yes, then separate them from the non-numeric accordingly
            if (!ctype_alpha($bd)) {
                $splitByDay = str_split($bd);
                $i = 0;

                if (strcmp($splitByDay[$i], "+") === 0) {
                    self::$logger = Logger::getInstance();
                    self::$logger->info("Encountered the character \"+\" at the beginning of the iCalendar BYDAY
                    property during processing of RRULE");

                    // Remove the "+" character from the string
                    // Since the string here is turned into an array, we make use of array_shift()
                    // in order to get rid of the "+" character
                    array_shift($splitByDay);
                }

                while (is_numeric($splitByDay[$i]) || strcmp($splitByDay[$i], "-") === 0) {
                    $i++;
                }

                $byDayWeekNumberString = substr(implode($splitByDay), 0, $i);
                $byDayWeekDayString = substr(implode($splitByDay), $i);
            } else {
                $byDayWeekDayString = $bd;
            }

            $jmapNDay = new NDay();
            $jmapNDay->setDay($byDayWeekDayString);
            if (!is_null($byDayWeekNumberString) && isset($byDayWeekNumberString)) {
                $jmapNDay->setNthOfPeriod((int) $byDayWeekNumberString);
            }

            array_push($jmapByDay, $jmapNDay);
        }

        return $jmapByDay;
    }

    public static function convertFromICalByMonthDayToJmapByMonthDay($byMonthDay)
    {
        if (is_null($byMonthDay)) {
            return null;
        }

        $splitByMonthDay = explode(",", $byMonthDay);

        foreach ($splitByMonthDay as $s) {
            $s = (int) $s;
        }

        return $splitByMonthDay;
    }

    public static function convertFromICalByMonthToJmapByMonth($byMonth)
    {
        if (is_null($byMonth)) {
            return null;
        }

        $splitByMonth = explode(",", $byMonth);

        return $splitByMonth;
    }

    public static function convertFromICalByYearDayToJmapByYearDay($byYearDay)
    {
        if (is_null($byYearDay)) {
            return null;
        }

        $splitByYearDay = explode(",", $byYearDay);

        foreach ($splitByYearDay as $s) {
            $s = (int) $s;
        }

        return $splitByYearDay;
    }

    public static function convertFromICalByWeekNoToJmapByWeekNo($byWeekNo)
    {
        if (is_null($byWeekNo)) {
            return null;
        }

        $splitByWeekNo = explode(",", $byWeekNo);

        foreach ($splitByWeekNo as $s) {
            $s = (int) $s;
        }

        return $splitByWeekNo;
    }

    public static function convertFromICalByHourToJmapByHour($byHour)
    {
        if (is_null($byHour)) {
            return null;
        }

        $splitByHour = explode(",", $byHour);

        foreach ($splitByHour as $s) {
            $s = (int) $s;
        }

        return $splitByHour;
    }

    public static function convertFromICalByMinuteToJmapByMinute($byMinute)
    {
        if (is_null($byMinute)) {
            return null;
        }

        $splitByMinute = explode(",", $byMinute);

        foreach ($splitByMinute as $s) {
            $s = (int) $s;
        }

        return $splitByMinute;
    }

    public static function convertFromICalBySecondToJmapBySecond($bySecond)
    {
        if (is_null($bySecond)) {
            return null;
        }

        $splitBySecond = explode(",", $bySecond);

        foreach ($splitBySecond as $s) {
            $s = (int) $s;
        }

        return $splitBySecond;
    }

    public static function convertFromICalBySetPositionToJmapBySetPos($bySetPosition)
    {
        if (is_null($bySetPosition)) {
            return null;
        }

        $splitBySetPosition = explode(",", $bySetPosition);

        foreach ($splitBySetPosition as $s) {
            $s = (int) $s;
        }

        return $splitBySetPosition;
    }

    public static function convertFromICalCountToJmapCount($count)
    {
        if (is_null($count)) {
            return null;
        }

        return (int) $count;
    }

    public static function convertFromICalUntilToJmapUntil($until)
    {
        if (is_null($until)) {
            return null;
        }

        // The UNTIL part of an iCalendar RRULE can have a different date format depending on Roundcube version
        // That's why we have both formats
        // Example for UNTIL with old format: 20210830
        // Example for UNTIL with new format: 20211120T003000Z
        $oldRoundcubeUntilDateFormat = "Ymd";
        $newRoundcubeUntilDateFormat = "Ymd\THis\Z";

        // First we try to parse UNTIL according to the new format
        $iCalUntilDate = \DateTime::createFromFormat($newRoundcubeUntilDateFormat, $until);

        // If parsing according to the new format does not work (i.e. createFromFormat returns false),
        // then try parsing according to the old format
        if ($iCalUntilDate === false) {
            $iCalUntilDate = \DateTime::createFromFormat($oldRoundcubeUntilDateFormat, $until);
        }

        $jmapUntil = date_format($iCalUntilDate, "Y-m-d\TH:i:s");

        return $jmapUntil;
    }

    public static function convertFromICalCUTypeToJmapKind($cutype)
    {
        if (is_null($cutype)) {
            return null;
        }

        $jmapKind = null;

        switch ($cutype) {
            case 'INDIVIDUAL':
                $jmapKind = "individual";
                break;

            case 'GROUP':
                $jmapKind = "group";
                break;

            case 'RESOURCE':
                $jmapKind = "resource";
                break;

            case 'ROOM':
                $jmapKind = "location";
                break;

            case 'UNKNOWN':
                $jmapKind = null;
                break;

            default:
                $jmapKind = strtolower($cutype);
                break;
        }

        return $jmapKind;
    }

    public static function convertFromICalRoleToJmapRole($role)
    {
        if (is_null($role)) {
            return null;
        }

        $jmapRoles = null;

        switch ($role) {
            case 'CHAIR':
                $jmapRoles = array("attendee" => true, "chair" => true);
                break;

            case 'REQ-PARTICIPANT':
                $jmapRoles = array("attendee" => true);
                break;

            case 'OPT-PARTICIPANT':
                $jmapRoles = array("attendee" => true, "optional" => true);
                break;

            case 'NON-PARTICIPANT':
                $jmapRoles = array("informational" => true);
                break;

            default:
                $jmapRoles = array(strtolower($role) => true);
                break;
        }

        return $jmapRoles;
    }

    /**
     * Takes a multi-dimensional array and flattens it to a single-dimensional array
     *
     * @param array $array
     * @return array|null Returns the single-dimensional array or null, if the passed parameter was empty
     */
    public static function flattenMultiDimensionalArray($array)
    {
        // If the array, passed as parameter, is either null, not set, empty or not an array, then return null
        if (is_null($array) || !isset($array) || empty($array) || !is_array($array)) {
            return null;
        }

        // Initialize return array
        $returnArray = array();
        // Initialize stack
        $stack = array_values($array);
        // Process stack until done
        while ($stack) {
            $value = array_shift($stack);
            if (is_array($value)) { // A value to further process
                array_unshift($stack, ...$value);
            } else { // A value to take
                $returnArray[] = $value;
            }
        }

        return $returnArray;
    }
}

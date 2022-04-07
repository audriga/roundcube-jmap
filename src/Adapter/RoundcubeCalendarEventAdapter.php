<?php

namespace OpenXPort\Adapter;

use OpenXPort\Util\RoundcubeCalendarEventAdapterUtil;
use OpenXPort\Adapter\AbstractAdapter;
use OpenXPort\Jmap\Calendar\Alert;
use OpenXPort\Jmap\Calendar\Location;
use OpenXPort\Jmap\Calendar\RecurrenceRule;
use OpenXPort\Jmap\Calendar\Link;
use OpenXPort\Jmap\Calendar\OffsetTrigger;
use OpenXPort\Jmap\Calendar\Participant;

class RoundcubeCalendarEventAdapter extends AbstractAdapter
{
    private $iCalEvent;

    public function getICalEvent()
    {
        return $this->iCalEvent;
    }

    public function setICalEvent($iCalEvent)
    {
        $this->iCalEvent = $iCalEvent;
    }

    public function getDTStart()
    {
        $dtStart = $this->iCalEvent->data["DTSTART"];
        $date = \DateTime::createFromFormat("Ymd\THis\Z", $dtStart->getValues());

        // If there's no 'Z' at the end of the date, try to parse the date without it
        if ($date === false) {
            $date = \DateTime::createFromFormat("Ymd\THis", $dtStart->getValues());
        }

        if ($date === false) {
            $date = \DateTime::createFromFormat("Ymd", $dtStart->getValues());
            $jmapStart = \date_format($date, "Y-m-d");

            // Add default values for time in the 'start' JMAP property
            $jmapStart .= "T00:00:00";

            return $jmapStart;
        }

        $jmapStart = \date_format($date, "Y-m-d\TH:i:s");
        return $jmapStart;
    }

    public function getDuration()
    {
        $dtStart = $this->iCalEvent->data["DTSTART"];
        $dtEnd = $this->iCalEvent->data["DTEND"];
        $format = "Ymd\THis";
        $formatWithZ = "Ymd\THis\Z";

        $dateStart = \DateTime::createFromFormat($formatWithZ, $dtStart->getValues());
        $dateEnd = \DateTime::createFromFormat($formatWithZ, $dtEnd->getValues());

        // Analogically to getDTStart(), try different parsing strategy for dates, in case they didn't parse correctly
        // at first
        if ($dateStart === false || $dateEnd === false) {
            $dateStart = \DateTime::createFromFormat($format, $dtStart->getValues());
            $dateEnd = \DateTime::createFromFormat($format, $dtEnd->getValues());
        }

        if ($dateStart === false || $dateEnd === false) {
            $dateStart = \DateTime::createFromFormat("Ymd", $dtStart->getValues());
            $dateEnd = \DateTime::createFromFormat("Ymd", $dtEnd->getValues());
        }

        $interval = $dateEnd->diff($dateStart);

        return $interval->format('P%dDT%hH%IM');
    }

    public function getStatus()
    {
        $status = $this->iCalEvent->data["STATUS"];

        if (is_null($status)) {
            return null;
        }

        switch ($status->getValues()) {
            case 'TENTATIVE':
                return "tentative";
                break;

            case 'CONFIRMED':
                return "confirmed";
                break;

            case 'CANCELLED':
                return "cancelled";
                break;

            default:
                return null;
                break;
        }
    }

    public function getUid()
    {
        $uid = $this->iCalEvent->data["UID"];

        if (is_null($uid)) {
            return null;
        }

        return $uid->getValues();
    }

    public function getProdId()
    {
        $prodId = null;

        $parentNode = $this->iCalEvent->parentnode;

        /**
         * Take 'prodId' property value from parent node
         * In case that the parent node is not a VCALENDAR object, but, e.g., another VEVENT object,
         * then iterate further until we reach a VCALENDAR object
         */
        while (strcmp($parentNode->name, "VCALENDAR") !== 0) {
            $parentNode = $parentNode->parentnode;
        }

        $prodId = $parentNode->data["PRODID"];

        if (is_null($prodId)) {
            return null;
        }

        return $prodId->getValues();
    }

    public function getCreated()
    {
        $created = $this->iCalEvent->data['CREATED'];

        if (is_null($created)) {
            return null;
        }

        $iCalFormat = 'Ymd\THis\Z';
        $jmapFormat = 'Y-m-d\TH:i:s\Z';

        $dateCreated = \DateTime::createFromFormat($iCalFormat, $created->getValues());
        $jmapCreated = date_format($dateCreated, $jmapFormat);

        return $jmapCreated;
    }

    public function getLastModified()
    {
        $lastModified = $this->iCalEvent->data['LAST-MODIFIED'];

        if (is_null($lastModified)) {
            return null;
        }

        $iCalFormat = 'Ymd\THis\Z';
        $jmapFormat = 'Y-m-d\TH:i:s\Z';

        $dateLastModified = \DateTime::createFromFormat($iCalFormat, $lastModified->getValues());
        $jmapLastModified = date_format($dateLastModified, $jmapFormat);

        return $jmapLastModified;
    }

    public function getSequence()
    {
        $sequence = $this->iCalEvent->data['SEQUENCE'];

        if (is_null($sequence)) {
            return 0;
        }

        return $sequence->getValues();
    }

    public function getSummary()
    {
        $summary = $this->iCalEvent->data["SUMMARY"];

        if (is_null($summary)) {
            return null;
        }

        return $summary->getValues();
    }

    public function getDescription()
    {
        $description = $this->iCalEvent->data["DESCRIPTION"];

        if (is_null($description)) {
            return null;
        }

        return $description->getValues();
    }

    public function getShowWithoutTime()
    {
        $dtStart = $this->iCalEvent->data["DTSTART"];
        $dtEnd = $this->iCalEvent->data["DTEND"];

        // Full day format for dates, e.g. 20210615, where 'Y' is year (2021), 'm' month (06) and 'd' day (15)
        // See https://www.php.net/manual/en/datetime.createfromformat.php
        $fullDayDateFormat = "Ymd";

        $dateStart = \DateTime::createFromFormat($fullDayDateFormat, $dtStart->getValues());
        $dateEnd = \DateTime::createFromFormat($fullDayDateFormat, $dtEnd->getValues());

        /**
         * If createFromFormat() above parses successfully for DTSTART's and DTEND's full day format,
         * this means that both of these dates do not include time, i.e. are formatted without time.
         * Based on this, we set the JMAP property 'showWithoutTime' to true to indicate a full day event.
         */
        if ($dateStart !== false && $dateEnd !== false) {
            return true;
        }

        return false;
    }

    public function getLocation()
    {
        $location = $this->iCalEvent->data['LOCATION'];

        if (is_null($location)) {
            return null;
        }

        $jmapLocations = [];

        $locationValue = $location->getValues();

        $jmapLocation = new Location();
        $jmapLocation->setType("Location");
        $jmapLocation->setName($locationValue);

        // Create a random string; I'm picking base64 as a random option
        $key = base64_encode($locationValue);
        $jmapLocations["$key"] = $jmapLocation;

        return $jmapLocations;
    }

    public function getLinks()
    {

        $jmapLinks = [];

        //
        // URL
        //
        if (array_key_exists("URL", $this->iCalEvent->data)) {
            $url = $this->iCalEvent->data["URL"];

            if (!is_null($url)) {
                $urlValue = $url->getValues();

                $jmapLink = new Link();
                $jmapLink->setType("Link");
                $jmapLink->setHref($urlValue);

                // Create a random string; I'm picking base64 as a random option
                $key = base64_encode($urlValue);
                $jmapLinks["$key"] = $jmapLink;
            }
        }

        //
        // ATTACHMENTS (Inline)
        //
        if (array_key_exists("ATTACH", $this->iCalEvent->data)) {
            $attach = $this->iCalEvent->data["ATTACH"];

            // Multiple file case
            if (is_array($attach)) {
                foreach ($attach as $att) {
                    $file = $this->convertFile($att);

                    if (!is_null($file)) {
                        // Create a random string; I'm picking base64 as a random option
                        $key = base64_encode(uniqid());
                        $jmapLinks["$key"] = $file;
                    }
                }

            // Single file case
            } else {
                $file = $this->convertFile($attach);

                if (!is_null($file)) {
                    // Create a random string; I'm picking base64 as a random option
                    $key = base64_encode(uniqid());
                    $jmapLinks["$key"] = $file;
                }
            }
        }

        if (sizeof($jmapLinks) == 0) {
            return null;
        }

        return $jmapLinks;
    }

    public function convertFile($attach)
    {

        // ATTACH;VALUE=BINARY;ENCODING=BASE64;FMTTYPE=image/jpeg;X-LABEL=test.jpg:..

        $jmapLink = new Link();
        $jmapLink->setRel("enclosure");

        $val = $attach->getParameter("value");
        $enc = $attach->getParameter("encoding");

        if ($val == "BINARY" && $enc == "BASE64") {
            $ctype = $attach->getParameter("fmttype");
            $base64 = $attach->getValues();
            $jmapLink->setHref("data:" . $ctype . "," . $base64);
            $jmapLink->setContentType($ctype);
            $jmapLink->setTitle($attach->getParameter("x-label"));
            return $jmapLink;
        }

        return null;
    }

    public function getFreeBusy()
    {

        if (!array_key_exists("TRANSP", $this->iCalEvent->data)) {
            return null;
        }

        $transp = $this->iCalEvent->data["TRANSP"];

        switch ($transp->getValues()) {
            case 'OPAQUE':
                return "busy";

            case 'TRANSPARENT':
                return "free";

            default:
                return null;
        }
    }

    public function getAlerts()
    {

        if (sizeof($this->iCalEvent->child) == 0) {
            return null;
        }

        $jmapAlerts = [];

        foreach ($this->iCalEvent->child as $childNode) {
            if ($childNode->getName() == 'VALARM') {
                // TODO actual conversion is more complex

                $trigger = new OffsetTrigger();
                $trigger->setType("OffsetTrigger");
                $trigger->setOffset($childNode->data["TRIGGER"]->getValues());

                $alert = new Alert();
                // TODO current default
                $alert->setAction("display");
                $alert->setTrigger($trigger);

                // Create a random string; I'm picking base64 as a random option
                $key = base64_encode($urlValue);
                $jmapAlerts["$key"] = $alert;
            }
        }

        if (sizeof($jmapAlerts) == 0) {
            return null;
        }

        return $jmapAlerts;
    }

    public function getCategories()
    {
        $categories = $this->iCalEvent->data['CATEGORIES'];

        if (is_null($categories)) {
            return null;
        }

        $jmapKeywords = [];

        $categoryValues = explode(",", $categories->getValues());

        foreach ($categoryValues as $c) {
            $jmapKeywords[$c] = true;
        }

        return $jmapKeywords;
    }

    public function getRRule()
    {
        $rRule = $this->iCalEvent->data['RRULE'];

        if (is_null($rRule)) {
            return null;
        }

        $rRuleValues = $rRule->getValues();

        // The library treats commas in RRULE as separator for rules.
        // We fix this by putting the separated RRULE back together as one whole (and not as separate rules)
        if (!empty($rRule->getValues()) && count($rRule->getValues()) > 1) {
            $rRuleValues = implode(",", $rRule->getValues());
        }

        $jmapRecurrenceRule = new RecurrenceRule();
        $jmapRecurrenceRule->setType("RecurrenceRule");

        foreach (explode(";", $rRuleValues) as $r) {
            // Split each rule string by '='. Sets its value into the RecurrenceRule, based upon its key (e.g. FREQ)
            $splitRule = explode("=", $r);
            $key = $splitRule[0];
            $value = $splitRule[1];

            switch ($key) {
                case 'FREQ':
                    $jmapRecurrenceRule->setFrequency(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalFreqToJmapFrequency($value)
                    );
                    break;

                case 'INTERVAL':
                    $jmapRecurrenceRule->setInterval(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalIntervalToJmapInterval($value)
                    );
                    break;

                case 'RSCALE':
                    $jmapRecurrenceRule->setRscale(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalRScaleToJmapRScale($value)
                    );
                    break;

                case 'SKIP':
                    $jmapRecurrenceRule->setSkip(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalSkipToJmapSkip($value)
                    );
                    break;

                case 'WKST':
                    $jmapRecurrenceRule->setFirstDayOfWeek(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalWKSTToJmapFirstDayOfWeek($value)
                    );
                    break;

                case 'BYDAY':
                    $jmapRecurrenceRule->setByDay(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByDayToJmapByDay($value)
                    );
                    break;

                case 'BYMONTHDAY':
                    $jmapRecurrenceRule->setByMonthDay(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByMonthDayToJmapByMonthDay($value)
                    );
                    break;

                case 'BYMONTH':
                    $jmapRecurrenceRule->setByMonth(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByMonthToJmapByMonth($value)
                    );
                    break;

                case 'BYYEARDAY':
                    $jmapRecurrenceRule->setByYearDay(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByYearDayToJmapByYearDay($value)
                    );
                    break;

                case 'BYWEEKNO':
                    $jmapRecurrenceRule->setByWeekNo(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByWeekNoToJmapByWeekNo($value)
                    );
                    break;

                case 'BYHOUR':
                    $jmapRecurrenceRule->setByHour(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByHourToJmapByHour($value)
                    );
                    break;

                case 'BYMINUTE':
                    $jmapRecurrenceRule->setByMinute(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalByMinuteToJmapByMinute($value)
                    );
                    break;

                case 'BYSECOND':
                    $jmapRecurrenceRule->setBySecond(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalBySecondToJmapBySecond($value)
                    );
                    break;

                case 'BYSETPOS':
                    $jmapRecurrenceRule->setBySetPosition(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalBySetPositionToJmapBySetPos($value)
                    );
                    break;

                case 'COUNT':
                    $jmapRecurrenceRule->setCount(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalCountToJmapCount($value)
                    );
                    break;

                case 'UNTIL':
                    $jmapRecurrenceRule->setUntil(
                        RoundcubeCalendarEventAdapterUtil::convertFromICalUntilToJmapUntil($value)
                    );
                    break;

                default:
                    // Maybe log something about an unexpected property/value in the parsed iCal RRULE?
                    break;
            }
        }

        return $jmapRecurrenceRule;
    }

    public function getExDate()
    {
        $exDate = $this->iCalEvent->data['EXDATE'];

        if (is_null($exDate)) {
            return null;
        }

        $splitExDateValues = explode(",", $exDate->getValues());

        $jmapRecurrenceOverrides = [];

        foreach ($splitExDateValues as $v) {
            $iCalFormat = 'Ymd\THis';
            $jmapFormat = 'Y-m-d\TH:i:s';

            $dateExDate = \DateTime::createFromFormat($iCalFormat, $v);
            $jmapExcludedRecurrenceOverride = date_format($dateExDate, $jmapFormat);

            $jmapRecurrenceOverrides[$jmapExcludedRecurrenceOverride] = array("excluded" => true);
        }

        return $jmapRecurrenceOverrides;
    }

    public function getPriority()
    {
        $priority = $this->iCalEvent->data['PRIORITY'];

        if (is_null($priority)) {
            return null;
        }

        return $priority->getValues();
    }

    public function getClass()
    {
        $class = $this->iCalEvent->data['CLASS'];

        if (is_null($class)) {
            return null;
        }

        switch ($class->getValues()) {
            case 'PUBLIC':
                return "public";

            case 'PRIVATE':
                return "private";

            case 'CONFIDENTIAL':
                return "secret";

            default:
                return null;
        }
    }

    public function getReplyTo()
    {
        $organizer = $this->iCalEvent->data["ORGANIZER"];

        if (is_null($organizer)) {
            return null;
        }

        $replyToMethod = null;
        $replyToValue = null;

        $organizerValue = $organizer->getValues();

        // The 'ORGANIZER' value seems to always be of the form 'mailto:<some-email>'
        // Thus, we split the value by ':' and set the JMAP 'replyTo' property
        // with the key and value after splitting, respectively
        $splitOrganizerValue = explode(":", $organizerValue);

        // If the length of the split string is 2 and the first component is 'mailto', then set method to 'imip'
        // Otherwise, set the method to 'other'
        // See: https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-icalendar-04#section-5.22
        if (count($splitOrganizerValue) === 2 && strcmp($splitOrganizerValue[0], "mailto") === 0) {
            $replyToMethod = "imip";
            $replyToValue = $splitOrganizerValue[1];
        } else {
            $replyToMethod = "other";
            $replyToValue = $splitOrganizerValue[1];
        }

        return array("$replyToMethod" => "$replyToValue");
    }

    public function getParticipants()
    {
        $attendee = $this->iCalEvent->data["ATTENDEE"];

        // Make sure to flatten $attendee, since it could be a multi-dimensional array due to a
        // potential issue in the iCalendar library that we use for iCalendar parsing.
        // See more info here: https://web.audriga.com/mantis/view.php?id=5476
        // Only apply the flattening if $attendee is not null, set, not empty and an array, otherwise
        // we might end up with an issue as in https://web.audriga.com/mantis/view.php?id=5727
        if (!is_null($attendee) && isset($attendee) && !empty($attendee) && is_array($attendee)) {
            $attendee = RoundcubeCalendarEventAdapterUtil::flattenMultiDimensionalArray($attendee);
        }

        $organizer = $this->iCalEvent->data["ORGANIZER"];

        $jmapParticipants = [];

        if (!is_null($attendee)) {
            // 'ATTENDEE' can either be array (if more than one attendees set) or an object
            // To avoid code duplication for attendee migration: force it to be an array
            if (!is_array($attendee)) {
                $attendee = array($attendee);
            }

            foreach ($attendee as $a) {
                $jmapParticipant = new Participant();
                $jmapParticipant->setType("Participant");

                // Take the value from ATTENDEE, split it by ':' and omit the first part
                // Then, use the second part to set in the 'sendTo' prop of the JSCalendar Participant
                // See https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-icalendar-04#section-5.2
                $attendeeValue = explode(":", $a->getValues());

                $jmapParticipant->setSendTo(array("imip" => $attendeeValue[1]));

                if (array_key_exists("CN", $a->getParameters())) {
                    $jmapParticipant->setName($a->getParameters()["CN"]);
                }

                if (array_key_exists("cutype", $a->getParameters())) {
                    $cuType = $a->getParameters()["cutype"];
                    $jmapKind = RoundcubeCalendarEventAdapterUtil::convertFromICalCUTypeToJmapKind($cuType);
                    $jmapParticipant->setKind($jmapKind);
                }

                if (array_key_exists("role", $a->getParameters())) {
                    $role = $a->getParameters()["role"];
                    $jmapRoles = RoundcubeCalendarEventAdapterUtil::convertFromICalRoleToJmapRole($role);
                    $jmapParticipant->setRoles($jmapRoles);
                }

                if (array_key_exists("partstat", $a->getParameters())) {
                    $partStat = $a->getParameters()["partstat"];
                    if (!is_null($partStat)) {
                        $jmapParticipant->setParticipationStatus(\strtolower($partStat));
                    }
                }

                if (array_key_exists("rsvp", $a->getParameters())) {
                    $rsvp = $a->getParameters()["rsvp"];
                    if (strcmp($rsvp, "TRUE") === 0) {
                        $jmapParticipant->setExpectReply(true);
                    }
                }

                // Generate a unique participant ID and add it as a key to 'participants'
                // See https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-32#section-4.4.5
                $participantId = md5(uniqid(rand(), true));

                $jmapParticipants["$participantId"] = $jmapParticipant;
            }
        }

        if (!is_null($organizer)) {
            $jmapParticipant = new Participant();
            $jmapParticipant->setType("Participant");

            // Take the value from ORGANIZER, split it by ':' and omit the first part
            // Then, use the second part to set in the 'sendTo' prop of the JSCalendar Participant
            // See https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-icalendar-04#section-5.22
            $organizerValue = explode(":", $organizer->getValues());

            $jmapParticipant->setSendTo(array("imip" => $organizerValue[1]));

            if (array_key_exists("CN", $organizer->getParameters())) {
                $jmapParticipant->setName($organizer->getParameters()["CN"]);
            }

            if (array_key_exists("CUTYPE", $organizer->getParameters())) {
                $jmapKind = RoundcubeCalendarEventAdapterUtil::convertFromICalCUTypeToJmapKind(
                    $organizer->getParameters()["CUTYPE"]
                );
                $jmapParticipant->setKind($jmapKind);
            }

            $jmapParticipant->setRoles(array("owner" => true));

            // Organizer does not have Status in Roundcube (probably not in others as well?)

            $jmapParticipant->setExpectReply(false);

            // Generate a unique participant ID and add it as a key to 'participants'
            // See https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-32#section-4.4.5
            $participantId = md5(uniqid(rand(), true));

            $jmapParticipants["$participantId"] = $jmapParticipant;
        }

        return $jmapParticipants;
    }

    public function getTimeZone()
    {
        //$timezoneComponent = $this->iCalEvent->parentNode->tree->child['VTIMEZONE'];
        //return $timezoneComponent;

        if (!array_key_exists("DTSTART", $this->iCalEvent->data)) {
            return null;
        }

        $dtStart = $this->iCalEvent->data["DTSTART"];
        if (array_key_exists("tzid", $dtStart->getParameters())) {
            return $dtStart->getParameter("tzid");
        }

        return null;
    }
}

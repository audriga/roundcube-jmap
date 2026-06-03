<?php
namespace OpenXPort\Adapter;

/**
 * Roundcube-specific adapter for JSCalendar <-> iCalendar conversion.
 *
 * Extends JSCalendarICalendarAdapter to handle Roundcube's native
 * event array format from the calendar plugin database driver.
 */
class RoundcubeJSCalendarICalendarAdapter extends JSCalendarICalendarAdapter
{
    /**
     * Get Roundcube event as hash (iCalendar string + oxpProperties).
     * Overrides parent to also include calendarId from Roundcube event.
     */
    public function getAsHash()
    {
        return array(
            "iCalendar" => $this->getICalEvent()->serialize(),
            "oxpProperties" => $this->getOXPProperties()
        );
    }

    /**
     * Set adapter from Roundcube event array.
     * Converts Roundcube's native format to iCalendar for processing.
     *
     * @param array $rcEvent Roundcube event array with keys: title, start, end, uid, etc.
     */
    public function setFromRcEvent(array $rcEvent)
    {
        // Store calendarId in oxpProperties
        if (isset($rcEvent['calendar'])) {
            $this->setCalendarId((string)$rcEvent['calendar']);
        }

        // Set UID
        if (!empty($rcEvent['uid'])) {
            $this->setUid($rcEvent['uid']);
        }

        // Set title as SUMMARY
        if (!empty($rcEvent['title'])) {
            $this->setSummary($rcEvent['title']);
        }

        // Set description
        if (!empty($rcEvent['description'])) {
            $this->setDescription($rcEvent['description']);
        }

        // Set start
        if (!empty($rcEvent['start'])) {
            $start = $rcEvent['start'] instanceof \DateTime
                ? $rcEvent['start']->format('Y-m-d\TH:i:s')
                : date('Y-m-d\TH:i:s', strtotime($rcEvent['start']));

            $timeZone = $rcEvent['start'] instanceof \DateTime
                ? $rcEvent['start']->getTimezone()->getName()
                : 'Etc/UTC';

            $this->setDTStart($start, $timeZone);
        }

        // Set end
        if (!empty($rcEvent['end'])) {
            $start = $rcEvent['start'] instanceof \DateTime
                ? $rcEvent['start']->format('Y-m-d\TH:i:s')
                : date('Y-m-d\TH:i:s', strtotime($rcEvent['start']));

            $end = $rcEvent['end'] instanceof \DateTime
                ? $rcEvent['end']
                : new \DateTime($rcEvent['end']);

            $start_dt = $rcEvent['start'] instanceof \DateTime
                ? $rcEvent['start']
                : new \DateTime($rcEvent['start']);

            $interval = $start_dt->diff($end);
            $duration = $interval->format('P%dDT%hH%iM%sS');
            $duration = preg_replace('/T0H0M0S/', '', $duration);
            $duration = preg_replace('/0D/', '', $duration);

            $timeZone = $rcEvent['start'] instanceof \DateTime
                ? $rcEvent['start']->getTimezone()->getName()
                : 'Etc/UTC';

            $this->setDTEnd($start, 'PT1H', $timeZone);
        }

        // Set status
        if (!empty($rcEvent['status'])) {
            $this->setStatus($rcEvent['status']);
        }

        // Set location
        if (!empty($rcEvent['location'])) {
            $location = new \OpenXPort\Jmap\Calendar\Location();
            $location->setName($rcEvent['location']);
            $this->setLocation(['1' => $location]);
        }

        // Set priority
        if (!empty($rcEvent['priority'])) {
            $this->setPriority($rcEvent['priority']);
        }

        // Set categories/keywords
        if (!empty($rcEvent['categories'])) {
            $cats = array_fill_keys(
                array_map('trim', explode(',', $rcEvent['categories'])),
                true
            );
            $this->setCategories($cats);
        }
    }
}
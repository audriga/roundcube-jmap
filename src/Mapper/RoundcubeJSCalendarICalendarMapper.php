<?php
namespace OpenXPort\Mapper;

/**
 * Roundcube-specific mapper for JSCalendar <-> iCalendar conversion.
 * Extends JSCalendarICalendarMapper to handle Roundcube's native event array format.
 */
class RoundcubeJSCalendarICalendarMapper extends JSCalendarICalendarMapper
{
    /**
     * Override mapFromJmap to convert iCalendar output to Roundcube event array.
     */
    public function mapFromJmap($jmapData, $adapter)
    {
        $iCalMap = parent::mapFromJmap($jmapData, $adapter);
        $map = [];

        foreach ($iCalMap as $entry) {
            $creationId = key($entry);
            $eventData = reset($entry);

            if (is_null($eventData)) {
                array_push($map, [$creationId => null]);
                continue;
            }

            $iCalString = $eventData['iCalendar'] ?? null;
            $calId = $eventData["oxpProperties"]["calendarId"] ?? null; $calendarId = is_array($calId) ? array_key_first($calId) : $calId;

            if (!$iCalString) {
                array_push($map, [$creationId => null]);
                continue;
            }

            $vObject = \Sabre\VObject\Reader::read($iCalString);
            $vevent = $vObject->VEVENT;

            $start = $vevent->DTSTART->getDateTime();
            $end = isset($vevent->DTEND)
                ? $vevent->DTEND->getDateTime()
                : (clone $start)->modify('+1 hour');

            $rcEvent = [
                'calendar'    => $calendarId,
                'uid'         => (string)$vevent->UID,
                'title'       => (string)($vevent->SUMMARY ?? ''),
                'description' => (string)($vevent->DESCRIPTION ?? ''),
                'location'    => (string)($vevent->LOCATION ?? ''),
                'start'       => $start,
                'end'         => $end,
                'allday'      => 0,
                'status'      => strtolower((string)($vevent->STATUS ?? '')),
                'priority'    => (int)($vevent->PRIORITY ?? 0),
                'free_busy'   => 'busy',
            ];

            array_push($map, [$creationId => $rcEvent]);
        }

        return $map;
    }

    /**
     * Override mapToJmap to convert Roundcube event array to iCalendar using the adapter.
     */
    public function mapToJmap($data, $adapter)
    {
        $iCalData = [];

        foreach ($data as $eventId => $event) {
            if (is_array($event)) {
                // Use adapter's setFromRcEvent to convert Roundcube array to iCalendar
                $adapter->resetICalEvent();
                $adapter->setFromRcEvent($event);
                $iCalData[$eventId] = $adapter->getAsHash();
            } else {
                $iCalData[$eventId] = $event;
            }
        }

        return parent::mapToJmap($iCalData, $adapter);
    }
}
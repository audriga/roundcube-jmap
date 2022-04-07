<?php

namespace OpenXPort\Mapper;

use OpenXPort\Mapper\AbstractMapper;
use OpenXPort\Jmap\Calendar\CalendarEvent;

class RoundcubeCalendarEventMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        // TODO: Implement me
    }

    public function mapToJmap($data, $adapter)
    {
        require_once __DIR__ . '/../../icalendar/zapcallib.php';

        $list = [];

        /**
         * The Roundcube calendar plugin returns master recurring calendar events and their modified exception events
         * as separate VEVENT entries in an iCalendar string. We are therefore mapping iCalendar events to JMAP events:
         *
         *  - Create two lists - one for master recurring events and one for modified exceptions
         * which are part of said master events.
         *
         *  - Note: Modified exceptions are distinguishable by the presence of the 'RECURRENCE-ID' property in iCalendar
         * and they have the same 'UID' as their corresponding master events.
         *
         *  - Then, put all modified exceptions in the JMAP property 'recurrenceOverrides' of their master event
         */
        $masterEvents = [];
        $modExs = [];

        foreach ($data as $calendarFolderId => $iCalEvents) {
            // Parse the iCal events of the corresponding calendar folder
            $icalObj = new \ZCiCal($iCalEvents);
            $iCalChildren = $icalObj->curnode->child;

            // Split iCalendar events into master events and modified exception events as explained above
            foreach ($iCalChildren as $node) {
                if ($node->getName() == "VEVENT") {
                    if (isset($node->data['RECURRENCE-ID']) && !is_null($node->data['RECURRENCE-ID'])) {
                        /**
                         * Save modified exceptions to an array of arrays
                         * The inner array is a map of calendar folder ID to modified exception
                         * This way we don't lose information regarding the calendar folder ID
                         */
                        array_push($modExs, array("folderId" => $calendarFolderId, "modifiedException" => $node));
                    } else {
                        // Same comment as for modified exceptions applies for master events as well
                        array_push($masterEvents, array("folderId" => $calendarFolderId, "masterEvent" => $node));
                    }
                }
            }
        }

        /**
         * Iterate through all master events and map them according to JMAP format. Leave out the recurrenceOverrides
         * property, which is handled separately in an inner foreach-loop
         */
        foreach ($masterEvents as $masterEvent) {
            $adapter->setICalEvent($masterEvent["masterEvent"]);

            $jmapMasterEvent = new CalendarEvent();

            $jmapMasterEvent->setType("jsevent");
            $jmapMasterEvent->setUid($adapter->getUid());
            $jmapMasterEvent->setProdId($adapter->getProdId());
            $jmapMasterEvent->setCalendarId($masterEvent["folderId"]);
            $jmapMasterEvent->setCreated($adapter->getCreated());
            $jmapMasterEvent->setUpdated($adapter->getLastModified());

            $jmapMasterEvent->setTitle($adapter->getSummary());
            $jmapMasterEvent->setDescription($adapter->getDescription());

            $jmapMasterEvent->setStart($adapter->getDTStart());
            $jmapMasterEvent->setDuration($adapter->getDuration());
            $jmapMasterEvent->setTimeZone($adapter->getTimeZone());

            $jmapMasterEvent->setStatus($adapter->getStatus());
            $jmapMasterEvent->setSequence($adapter->getSequence());
            $jmapMasterEvent->setShowWithoutTime($adapter->getShowWithoutTime());
            $jmapMasterEvent->setLocations($adapter->getLocation());
            $jmapMasterEvent->setLinks($adapter->getLinks());
            $jmapMasterEvent->setKeywords($adapter->getCategories());
            $jmapMasterEvent->setRecurrenceRule($adapter->getRRule());
            $jmapMasterEvent->setRecurrenceOverrides($adapter->getExDate());

            $jmapMasterEvent->setPriority($adapter->getPriority());
            $jmapMasterEvent->setPrivacy($adapter->getClass());
            $jmapMasterEvent->setReplyTo($adapter->getReplyTo());
            $jmapMasterEvent->setParticipants($adapter->getParticipants());
            $jmapMasterEvent->setAlerts($adapter->getAlerts());
            $jmapMasterEvent->setFreeBusyStatus($adapter->getFreeBusy());


            // Take each master event's UID, since we're going to need it below in the foreach-loop
            $masterEventUid = $masterEvent["masterEvent"]->data['UID']->getValues();

            /**
             * Take all modified exceptions that match the currently iterated master event from the outer
             * foreach (note: modified exception and master event comparison is done via the UID iCalendar property)
             */
            foreach ($modExs as $modEx) {
                // Take each modified exception's UID, since we need it as described above
                $modifiedExceptionUid = $modEx["modifiedException"]->data['UID']->getValues();

                /**
                 * If a modified exception corresponds to a master event, we first transform the modified exception to
                 * JMAP (we only leave out the JMAP properties '@type', 'excludedRecurrenceRules', 'method', 'privacy',
                 * 'prodId', 'recurrenceId', 'recurrenceOverrides', 'recurrenceRules', 'relatedTo', 'replyTo' and 'uid'.
                 * They should not be included in recurrenceOverrides.
                 * See https://datatracker.ietf.org/doc/html/draft-ietf-calext-jscalendar-32#section-4.3.4)
                 */
                if (\strcmp($modifiedExceptionUid, $masterEventUid) === 0) {
                    $adapter->setICalEvent($modEx["modifiedException"]);

                    $jmapModifiedException = new CalendarEvent();

                    $jmapModifiedException->setCalendarId($modEx["folderId"]);
                    $jmapModifiedException->setCreated($adapter->getCreated());
                    $jmapModifiedException->setUpdated($adapter->getLastModified());

                    $jmapModifiedException->setTitle($adapter->getSummary());
                    $jmapModifiedException->setDescription($adapter->getDescription());

                    $jmapModifiedException->setStart($adapter->getDTStart());
                    $jmapModifiedException->setDuration($adapter->getDuration());
                    $jmapModifiedException->setTimeZone($adapter->getTimeZone());

                    $jmapModifiedException->setStatus($adapter->getStatus());
                    $jmapModifiedException->setSequence($adapter->getSequence());
                    $jmapModifiedException->setShowWithoutTime($adapter->getShowWithoutTime());
                    $jmapModifiedException->setLocations($adapter->getLocation());
                    $jmapModifiedException->setLinks($adapter->getLinks());
                    $jmapModifiedException->setKeywords($adapter->getCategories());

                    $jmapModifiedException->setPriority($adapter->getPriority());

                    $jmapModifiedException->setParticipants($adapter->getParticipants());
                    $jmapModifiedException->setAlerts($adapter->getAlerts());
                    $jmapModifiedException->setFreeBusyStatus($adapter->getFreeBusy());


                    /**
                     * Each JMAP modified exception is then set to be part of the 'recurrenceOverrides' property of its
                     * corresponding master event.
                     *
                     * First take the already present values in recurrenceOverrides from the master event (if any),
                     * then merge the content of the existing recurrenceOverrides with the JMAP modified exception
                     * from above.
                     */
                    $currentRecurrenceOverrides = $jmapMasterEvent->getRecurrenceOverrides();
                    if (is_null($currentRecurrenceOverrides)) {
                        $currentRecurrenceOverrides = [];
                    }

                    /**
                     * Since recurrenceOverrides is a map of RECURRENCE-ID value (date formatted as per JMAP)
                     * to the JMAP PatchObject of the event, we set the key of the map
                     * to the RECURRENCE-ID value and the map's value to the JMAP modified exception itself
                     */
                    $recurrenceIdValueString = $modEx["modifiedException"]->data["RECURRENCE-ID"]->getValues();
                    $recurrenceIdValueDate = \DateTime::createFromFormat("Ymd\THis", $recurrenceIdValueString);

                    if ($recurrenceIdValueDate === false) {
                        $recurrenceIdValueDate = \DateTime::createFromFormat("Ymd", $recurrenceIdValueString);
                    }

                    $recurrenceIdOfModifiedException = \date_format($recurrenceIdValueDate, "Y-m-d\TH:i:s");
                    $newRecurrenceOverrides = array($recurrenceIdOfModifiedException => $jmapModifiedException);

                    $currentRecurrenceOverrides = array_merge($currentRecurrenceOverrides, $newRecurrenceOverrides);

                    // Finally set recurrenceOverrides of the master event to contain the modified exceptions from above
                    $jmapMasterEvent->setRecurrenceOverrides($currentRecurrenceOverrides);
                }
            }

            array_push($list, $jmapMasterEvent);
        }

        return $list;
    }
}

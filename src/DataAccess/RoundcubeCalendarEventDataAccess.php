<?php

namespace OpenXPort\DataAccess;

use OpenXPort\DataAccess\AbstractDataAccess;

class RoundcubeCalendarEventDataAccess extends AbstractDataAccess
{
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting calendar events");

        // require plugin classes only if they have not already been loaded
        if (!class_exists('libcalendaring')) {
            require_once __DIR__ . '/../../../libcalendaring/libcalendaring.php';
        }
        if (!class_exists('calendar')) {
            require_once __DIR__ . '/../../../calendar/calendar.php';
        }

        $rcube_plugin_api = \rcube_plugin_api::get_instance();
        $calendar = new \calendar($rcube_plugin_api);
        $calendar->init();

        $result = [];

        /**
         * Since we need to specify start and end date for reading events (see 'load_events()' method below),
         * we set the start date to 01.01.1970 at 00:00.00 (in epoch: 0) and the end date
         * to 31.12.2100 at 23:59.59 (in epoch: 4133980799)
         */
        $eventsFetchStartTimestamp = 0;
        $eventsFetchEndTimestamp = 4133980799;

        /**
         * Take only calendar folders which belong to the user (i.e. filter them with FILTER_PERSONAL),
         * since we need to take only them and not all calendar folders from Roundcube
         *
         * There is also the option of FILTER_PRIVATE, but it is only used in the context of a configured Kolab backend
         * See here: https://github.com/kolab-roundcube-plugins-mirror/calendar/search?q=filter_private
         * and here: https://github.com/kolab-roundcube-plugins-mirror/calendar/blob/e11e26ed24c7a83c046322b01e1efbd080487c28/drivers/kolab/kolab_driver.php#L325
         *
         * Due to above listed reason, we currently prefer FILTER_PERSONAL over FILTER_PRIVATE
         */
        $calendarFolders = $calendar->__get('driver')->list_calendars(FILTER_PERSONAL);

        // Some cPanel versions seem to use different way of getting the driver
        // https://web.audriga.com/mantis/view.php?id=5433
        // Retry with CPanel-specific call in this case
        if (sizeof($calendarFolders) == 0) {
            $this->logger->notice("No calendars found. Trying it the custom cPanel way.");

            $cpanel_weird_mode = true;
            $calendarFolders = $calendar->get_calendars();
        }

        // Define a boolean flag to indicate if we're exporting attachments of iCalendar calendar events
        $exportICalendarAttachments = true;

        $totalEventCount = 0;

        /**
         * Get all associated calendar events via the method load_events().
         *
         * Then, we export each fetched calendar event to iCalendar format.
         *
         * Finally, return a map of calendar folder IDs and the their calendar events for each calendar folder ID,
         * so we can then, in the adapter, create a JMAP calendar event from each iCalendar calendar event and also
         * be able to set the correct calendar folder ID for each of the resulting JMAP calendar events.
         */
        foreach ($calendarFolders as $calendarFolder) {
            $calendarFolderId = $calendarFolder['id'];
            $driver = $calendar->__get('driver');

            if ($cpanel_weird_mode) {
                $driver = $calendar->get_driver_by_cal($calendarFolderId);
                $calendarFolderEvents = $driver->load_events(
                    $eventsFetchStartTimestamp,
                    $eventsFetchEndTimestamp,
                    null,
                    $calendarFolderId,
                    0
                );
            } else {
                $calendarFolderEvents = $driver->load_events(
                    $eventsFetchStartTimestamp,
                    $eventsFetchEndTimestamp,
                    null,
                    $calendarFolderId,
                    0
                );
            }

            // Use the flag from above for exporting attachments in iCalendar
            $calendarFolderICalEvents = $calendar->get_ical()->export(
                $calendarFolderEvents,
                '',
                false,
                $exportICalendarAttachments ? [$driver, 'get_attachment_body'] : null
            );

            $totalEventCount += sizeof($calendarFolderICalEvents);

            $result[$calendarFolderId] = $calendarFolderICalEvents;
        }

        $this->logger->info("Got " . $totalEventCount . " calendar events.");

        return $result;
    }

    public function get($ids, $accountId = null)
    {
        throw new BadMethodCallException("Get via CalendarEvent/get not implemented");
    }

    public function create($eventsToCreate, $accountId = null)
    {
        throw new BadMethodCallException("Create via CalendarEvent/set not implemented");
    }

    public function destroy($ids, $accountId = null)
    {
        throw new BadMethodCallException("Destroy via CalendarEvent/set not implemented");
    }

    public function query($accountId, $filter = null)
    {
        throw new BadMethodCallException("Query via CalendarEvent/set not implemented");
    }
}

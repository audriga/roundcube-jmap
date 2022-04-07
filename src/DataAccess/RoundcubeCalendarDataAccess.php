<?php

namespace OpenXPort\DataAccess;

use OpenXPort\DataAccess\AbstractDataAccess;

class RoundcubeCalendarDataAccess extends AbstractDataAccess
{
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting calendars");

        // require plugin classes only if they have not already been loaded
        if (!class_exists('calendar')) {
            require_once __DIR__ . '/../../../calendar/calendar.php';
        }

        $rcube_plugin_api = \rcube_plugin_api::get_instance();
        $calendar = new \calendar($rcube_plugin_api);
        $calendar->init();

        $calendarFolders = $calendar->__get('driver')->list_calendars();

        // Some cPanel versions seem to use different way of getting the driver
        // https://web.audriga.com/mantis/view.php?id=5433
        if (sizeof($calendarFolders) == 0) {
            $this->logger->notice("No calendars found. Trying it the custom cPanel way.");

            $calendarFolders = $calendar->get_calendars();
        }

        $this->logger->info("Got " . sizeof($calendarFolders) . " calendars.");

        return $calendarFolders;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function create($calendarsToCreate, $accountId = null)
    {
        // TODO: Implement me
    }

    public function destroy($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}

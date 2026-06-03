<?php
namespace OpenXPort\DataAccess;

/**
 * Data access class for Roundcube calendar events.
 * Uses the Roundcube calendar plugin's database_driver for CRUD operations.
 */
class RoundcubeCalendarEventDataAccess extends AbstractDataAccess
{
    /** @var database_driver Roundcube calendar database driver */
    private $driver;

    /** @var rcube_db Database handle for direct queries (changes tracking) */
    private $db;

    /** @var int Current user ID */
    private $userID;

    /** @var \OpenXPort\Util\Logger */
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();
        $RCMAIL = \rcmail::get_instance(0, $GLOBALS['env']);

        // Load calendar plugin and its database driver
        require_once '/var/www/html/plugins/calendar/drivers/calendar_driver.php';
        require_once '/var/www/html/plugins/calendar/drivers/database/database_driver.php';

        $cal = $RCMAIL->plugins->get_plugin('calendar');
        $this->driver = new \database_driver($cal);

        $this->db = $RCMAIL->get_dbh();
        $this->userID = $RCMAIL->user->ID;
    }

    /**
     * Returns all calendar events for the current user across all calendars.
     */
    public function getAll($accountId = null)
    {
        $this->logger->info("Getting calendar events");

        $result = [];

        $query = $this->db->query(
            "SELECT e.* FROM events e
            JOIN calendars c ON e.calendar_id = c.calendar_id
            WHERE c.user_id = ? AND e.recurrence_id = 0",
            $this->userID
        );

        while ($row = $this->db->fetch_assoc($query)) {
            $result[$row['event_id']] = $row;
        }

        $this->logger->info("Got " . count($result) . " calendar events.");
        return $result;
    }

    /**
     * Returns specific calendar events by their IDs.
     */
    public function get($ids, $accountId = null)
    {
        $result = [];

        foreach ($ids as $id) {
            $event = $this->driver->get_event(['id' => $id]);
            if ($event) {
                $result[$event['id']] = $event;
            }
        }

        return $result;
    }

    /**
     * Creates new calendar events.
     * Each element in $eventsToCreate is an array with creationId => eventData.
     */
    public function create($eventsToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . count($eventsToCreate) . " calendar events for user " . $accountId);

        $eventMap = [];

        foreach ($eventsToCreate as $c) {
            $eventToCreate = reset($c);
            $creationId = key($c);

            if (is_null($eventToCreate)) {
                $eventMap[$creationId] = false;
                continue;
            }

            // eventToCreate is already a Roundcube event array from the mapper
            // Fix calendarId if it came as a map {"1": true} instead of integer
            if (isset($eventToCreate['calendar']) && is_array($eventToCreate['calendar'])) {
                $eventToCreate['calendar'] = (int)array_key_first($eventToCreate['calendar']);
            }

            $newId = $this->driver->new_event($eventToCreate);
            $eventMap[$creationId] = $newId ? (string)$newId : false;
        }

        return $eventMap;
    }

    /**
     * Updates existing calendar events.
     */
    public function update($entitiesToUpdate, $accountId = null)
    {
        $updated = [];

        foreach ($entitiesToUpdate as $id => $event) {
            if (empty($event)) {
                $updated[$id] = false;
                continue;
            }

            $event['id'] = $id;
            $success = $this->driver->edit_event($event);
            $updated[$id] = (bool)$success;
        }

        return $updated;
    }

    /**
     * Deletes calendar events by their IDs.
     */
    public function destroy($ids, $accountId = null)
    {
        $eventMap = [];

        foreach ($ids as $id) {
            $success = $this->driver->remove_event(['id' => $id], true);
            $eventMap[$id] = $success ? 1 : 0;
        }

        return $eventMap;
    }

    /**
     * Returns IDs of all calendar events for the current user.
     */
    public function query($accountId = null, $filter = null)
    {
        $ids = [];

        $result = $this->db->query(
            "SELECT e.event_id FROM events e
            JOIN calendars c ON e.calendar_id = c.calendar_id
            WHERE c.user_id = ? AND e.recurrence_id = 0",
            $this->userID
        );

        while ($row = $this->db->fetch_assoc($result)) {
            $ids[] = (string)$row['event_id'];
        }

        return $ids;
    }

    /**
     * Returns the current state as a Unix timestamp of the latest event change.
     */
    public function getCurrentState($accountId = null)
    {
        $result = $this->db->query(
            "SELECT MAX(UNIX_TIMESTAMP(changed)) AS current_state FROM events e
             JOIN calendars c ON e.calendar_id = c.calendar_id
             WHERE c.user_id = ?",
            $this->userID
        );

        $row = $this->db->fetch_assoc($result);
        return ($row && $row['current_state']) ? (string)$row['current_state'] : "0";
    }

    /**
     * Returns IDs of events changed since sinceState (Unix timestamp).
     * Roundcube has no dedicated changes table, so all changes are reported as updated.
     */
    public function getChanges($sinceState, $maxChanges = 1000, $accountId = null)
    {
        $sinceDate = date('Y-m-d H:i:s', (int)$sinceState);

        $result = $this->db->limitquery(
            "SELECT e.event_id FROM events e
             JOIN calendars c ON e.calendar_id = c.calendar_id
             WHERE c.user_id = ? AND e.changed > ? ORDER BY e.changed ASC",
            0,
            $maxChanges + 1,
            $this->userID,
            $sinceDate
        );

        $rows = [];
        while ($row = $this->db->fetch_assoc($result)) {
            $rows[] = $row;
        }

        $hasMoreChanges = count($rows) > $maxChanges;
        if ($hasMoreChanges) {
            array_pop($rows);
        }

        $updated = [];
        foreach ($rows as $row) {
            $updated[] = (string)$row['event_id'];
        }

        $newState = empty($rows) ? $sinceState : $this->getCurrentState();

        return [
            'oldState' => $sinceState,
            'newState' => $newState,
            'hasMoreChanges' => $hasMoreChanges,
            'created' => [],
            'updated' => $updated,
            'destroyed' => []
        ];
    }
}
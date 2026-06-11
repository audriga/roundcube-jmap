<?php

namespace OpenXPort\DataAccess;

/**
 * Data access class for Roundcube calendars.
 * Uses direct DB queries against the calendars table.
 */
class RoundcubeCalendarDataAccess extends AbstractDataAccess
{
    private $db;
    private $userID;
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();
        $RCMAIL = \rcmail::get_instance(0, $GLOBALS['env']);
        $this->db = $RCMAIL->get_dbh();
        $this->userID = $RCMAIL->user->ID;
    }

    public function getAll($accountId = null)
    {
        $result = [];
        $query = $this->db->query(
            "SELECT * FROM calendars WHERE user_id = ?",
            $this->userID
        );
        while ($row = $this->db->fetch_assoc($query)) {
            $result[(string)$row['calendar_id']] = $row;
        }
        return $result;
    }

    public function get($ids, $accountId = null)
    {
        $result = [];
        foreach ($ids as $id) {
            $row = $this->db->fetch_assoc(
                $this->db->query(
                    "SELECT * FROM calendars WHERE calendar_id = ? AND user_id = ?",
                    $id,
                    $this->userID
                )
            );
            if ($row) {
                $result[(string)$row['calendar_id']] = $row;
            }
        }
        return $result;
    }

    public function create($calendarsToCreate, $accountId = null)
    {
        $created = [];
        foreach ($calendarsToCreate as $creationId => $data) {
            $name = isset($data['name']) ? $data['name'] : 'New Calendar';
            $color = isset($data['color']) ? ltrim($data['color'], '#') : 'cc0000';
            $this->db->query(
                "INSERT INTO calendars (user_id, name, color, showalarms) VALUES (?, ?, ?, 1)",
                $this->userID,
                $name,
                $color
            );
            $newId = $this->db->insert_id();
            $created[$creationId] = (string)$newId;
        }
        return $created;
    }

    public function update($calendarsToUpdate, $accountId = null)
    {
        $updated = [];
        foreach ($calendarsToUpdate as $id => $data) {
            $name = isset($data['name']) ? $data['name'] : null;
            $color = isset($data['color']) ? ltrim($data['color'], '#') : null;
            if ($name || $color) {
                $this->db->query(
                    "UPDATE calendars SET name = COALESCE(?, name), color = COALESCE(?, color) WHERE calendar_id = ? AND user_id = ?",
                    $name,
                    $color,
                    $id,
                    $this->userID
                );
            }
            $updated[$id] = true;
        }
        return $updated;
    }

    public function destroy($ids, $accountId = null)
    {
        $destroyed = [];
        foreach ($ids as $id) {
            $this->db->query(
                "DELETE FROM calendars WHERE calendar_id = ? AND user_id = ?",
                $id,
                $this->userID
            );
            $destroyed[$id] = 1;
        }
        return $destroyed;
    }

    public function query($accountId = null, $filter = null)
    {
        $ids = [];
        $query = $this->db->query(
            "SELECT calendar_id FROM calendars WHERE user_id = ?",
            $this->userID
        );
        while ($row = $this->db->fetch_assoc($query)) {
            $ids[] = (string)$row['calendar_id'];
        }
        return $ids;
    }

    public function getCurrentState($accountId = null)
    {
        return (string)time();
    }

    public function getChanges($sinceState, $maxChanges = 1000, $accountId = null)
    {
        return [
            'oldState' => $sinceState,
            'newState' => $this->getCurrentState(),
            'hasMoreChanges' => false,
            'created' => [],
            'updated' => [],
            'destroyed' => []
        ];
    }
}

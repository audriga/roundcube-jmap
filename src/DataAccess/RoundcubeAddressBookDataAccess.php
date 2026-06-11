<?php

namespace OpenXPort\DataAccess;

class RoundcubeAddressBookDataAccess extends AbstractDataAccess
{
    private $db;
    private $userID;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $RCMAIL = \rcmail::get_instance(0, $GLOBALS['env']);

        $this->db = \rcube_db::factory(
            $RCMAIL->config->get('db_dsnw'),
            $RCMAIL->config->get('db_dsnr'),
            $RCMAIL->config->get('db_persistent')
        );

        $this->userID = $RCMAIL->user->ID;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting address books");

        $result = [];
        $query = $this->db->query(
            "SELECT contactgroup_id, name FROM contactgroups WHERE user_id = ? AND del = 0",
            $this->userID
        );
        while ($row = $this->db->fetch_assoc($query)) {
            $result[$row['contactgroup_id']] = $row;
        }

        $this->logger->info("Got " . sizeof($result) . " address books.");

        return $result;
    }

    public function get($ids, $accountId = null)
    {
        $result = [];
        foreach ($ids as $id) {
            $row = $this->db->fetch_assoc(
                $this->db->query(
                    "SELECT contactgroup_id, name FROM contactgroups WHERE contactgroup_id = ? AND user_id = ? AND del = 0",
                    $id,
                    $this->userID
                )
            );
            if ($row) {
                $result[$row['contactgroup_id']] = $row;
            }
        }
        return $result;
    }

    public function create($addressBooksToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($addressBooksToCreate) . " address books for user " . $accountId);

        $addressBookMap = [];

        foreach ($addressBooksToCreate as $ab) {
            $addressBookToCreate = reset($ab);
            $creationId = key($ab);

            if (is_null($addressBookToCreate)) {
                $addressBookMap[$creationId] = false;
            } else {
                $name = isset($addressBookToCreate['name']) ? $addressBookToCreate['name'] : 'New Address Book';
                $this->db->query(
                    "INSERT INTO contactgroups (user_id, name, changed) VALUES (?, ?, NOW())",
                    $this->userID,
                    $name
                );
                $newId = $this->db->insert_id();
                $addressBookMap[$creationId] = (string)$newId;
            }
        }

        return $addressBookMap;
    }

    public function update($addressBooksToUpdate, $accountId = null)
    {
        $updated = [];
        foreach ($addressBooksToUpdate as $id => $data) {
            $name = isset($data['name']) ? $data['name'] : null;
            if ($name) {
                $this->db->query(
                    "UPDATE contactgroups SET name = ?, changed = NOW() WHERE contactgroup_id = ? AND user_id = ?",
                    $name,
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
        $addressBookMap = [];
        foreach ($ids as $id) {
            $res = $this->db->query(
                "UPDATE contactgroups SET del = 1 WHERE contactgroup_id = ? AND user_id = ?",
                $id,
                $this->userID
            );
            $addressBookMap[$id] = ($res !== false);
        }
        return $addressBookMap;
    }

    public function query($accountId = null, $filter = null)
    {
        $ids = [];
        $query = $this->db->query(
            "SELECT contactgroup_id FROM contactgroups WHERE user_id = ? AND del = 0",
            $this->userID
        );
        while ($row = $this->db->fetch_assoc($query)) {
            $ids[] = (string)$row['contactgroup_id'];
        }
        return $ids;
    }

    public function getCurrentState($accountId = null)
    {
        return (string)time();
    }

    public function getChanges($sinceState, $maxChanges = 500, $accountId = null)
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

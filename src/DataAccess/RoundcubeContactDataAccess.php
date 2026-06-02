<?php

namespace OpenXPort\DataAccess;

class RoundcubeContactDataAccess extends AbstractDataAccess
{
    // Since list_records() (the Roundcube method we use for reading contacts) requires
    // a max number of contacts to retrieve, we currently supply such a number with the value of 50000
    const NUMBER_OF_CONTACTS_RETRIEVED = 50000;

    private $contact_db;
    private $db;
    private $userID;
    private $logger;

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
        $this->contact_db = new \rcube_contacts($this->db, $this->userID);
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting contacts");

        // Read all contacts from Roundcube
        $contacts = $this->contact_db->list_records(null, self::NUMBER_OF_CONTACTS_RETRIEVED, true);

        $this->logger->info("Got " . sizeof($contacts->records) . " contacts.");

        // An array to hold the vCards of all contacts that we've read from Roundcube
        $result = [];

        // Iterate through all contacts that we retrieved above and add the contact's ID
        // and the contact's vCard as a key-value pair in $result
        // This way, we make sure that we don't lose any information regarding a contact's ID (since the vCard
        // does not contain a contact's ID, but we still need the ID anyway)
        foreach ($contacts as $c) {
            $contactId = $c['ID'];
            
            // Read raw vCard directly from DB to preserve custom properties
            $row = $this->db->fetch_assoc(
                $this->db->query("SELECT vcard FROM contacts WHERE contact_id = ?", $contactId)
            );
            $result[$contactId] = $row['vcard'];
        }

        // Return the contact IDs and the vCards that we gathered above
        return $result;
    }

    public function get($ids, $accountId = null)
    {
        $result = [];

        foreach ($ids as $id) {
            $row = $this->db->fetch_assoc(
                $this->db->query(
                    "SELECT contact_id, vcard FROM contacts WHERE contact_id = ? AND user_id = ? AND del = 0",
                    $id,
                    $this->userID
                )
            );

            if ($row && !empty($row['vcard'])) {
                $result[$row['contact_id']] = $row['vcard'];
            }
        }

        return $result;
    }

    public function create($contactsToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($contactsToCreate) . " contacts for user " . $accountId);

        $contactMap = [];

        foreach ($contactsToCreate as $c) {
            // $contactToCreate is a vCard that we receive
            $contactToCreate = reset($c);

            // $creationId is the creation ID that we send within a JMAP /set request
            // For more info, see the "create" argument for JMAP /set requests here: https://jmap.io/spec-core.html#set
            $creationId = key($c);

            // In case $contactToCreate is null, we shouldn't perform contact writing, but instead we should
            // write false as the value for the corresponding $creationId key in $contactMap
            if (is_null($contactToCreate)) {
                $contactMap[$creationId] = false;
            } else {
                // Create a rcube_vcard object from the vCard string in $contactToCreate via rcube_vcard's constructor
                $vCardObject = new \rcube_vcard($contactToCreate);

                // Then, on the created rcube_vcard object call the get_assoc() method which returns an
                // associative array representation of a contact that is suitable for saving into Roundcube
                $rcubeContactToSave = $vCardObject->get_assoc();

                // Finally, insert the associative array contact representation into Roundcube
                // via the insert() method.
                $newId = $this->contact_db->insert($rcubeContactToSave);
                $contactMap[$creationId] = $newId;

                // Update the vcard column with the full vCard string to preserve
                // custom properties (CATEGORIES, SOCIALPROFILE, NOTE, etc.)
                if ($newId) {
                    $this->db->query(
                        "UPDATE contacts SET vcard = ? WHERE contact_id = ?",
                        $contactToCreate,
                        $newId
                    );
                }
            }
        }

        return $contactMap;
    }

    // Destroys specific entities
    public function destroy($ids, $accountId = null)
    {
        $contactMap = [];

        foreach ($ids as $id) {
            $contactMap[$id] = $this->contact_db->delete($id, false);
        }

        return $contactMap;
    }

    // Collects multiple ids
    public function query($accountId, $filter = null)
    {
        $ids = [];

        $query = $this->db->query(
            "SELECT contact_id FROM contacts WHERE user_id = ? AND del = 0",
            $this->userID
        );

        while ($row = $this->db->fetch_assoc($query)) {
            $ids[] = (string) $row['contact_id'];
        }

        return ['ids' => $ids];
    }

    public function update($contactsToUpdate, $accountId = null)
    {
        $updated = [];
        foreach ($contactsToUpdate as $id => $vCard) {
            if (empty($vCard)) {
                $updated[$id] = false;
                continue;
            }

            // Parse the new vCard for standard fields
            $vCardObject = new \rcube_vcard($vCard);
            $rcubeContact = $vCardObject->get_assoc();

            // Update standard fields via Roundcube
            $this->contact_db->update($id, $rcubeContact);

            // Update the full vCard to preserve custom properties
            $this->db->query(
                "UPDATE contacts SET vcard = ? WHERE contact_id = ? AND user_id = ?",
                $vCard,
                $id,
                $this->userID
            );

            $updated[$id] = true;
        }
        return $updated;
    }

    /**
     * Returns the current state as a Unix timestamp of the latest contact change.
     * @see https://datatracker.ietf.org/doc/html/rfc8620#section-5.2
     */
    public function getCurrentState($accountId = null)
    {
        $result = $this->db->query(
            "SELECT MAX(UNIX_TIMESTAMP(changed)) AS current_state FROM contacts WHERE user_id = ?",
            $this->userID
        );

        $row = $this->db->fetch_assoc($result);

        return ($row && $row['current_state']) ? (string)$row['current_state'] : "0";
    }

    /**
     * Returns IDs of contacts created, updated, or destroyed since sinceState.
     * State is a Unix timestamp. Roundcube has no dedicated changes table, so
     * created and updated cannot be distinguished — all non-deleted changes are
     * reported as updated (RFC 8620 allows this conservative approach).
     * @see https://datatracker.ietf.org/doc/html/rfc8620#section-5.2
     */
    public function getChanges($sinceState, $maxChanges = 1000, $accountId = null)
    {
        $sinceDate = date('Y-m-d H:i:s', (int)$sinceState);

        $result = $this->db->limitquery(
            "SELECT contact_id, del FROM contacts WHERE user_id = ? AND changed > ? ORDER BY changed ASC",
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

        $changes = ['created' => [], 'updated' => [], 'destroyed' => []];

        foreach ($rows as $row) {
            $contactId = (string)$row['contact_id'];
            if ($row['del']) {
                $changes['destroyed'][] = $contactId;
            } else {
                $changes['updated'][] = $contactId;
            }
        }

        $newState = empty($rows) ? $sinceState : $this->getCurrentState();

        return array_merge($changes, [
            'newState' => $newState,
            'hasMoreChanges' => $hasMoreChanges,
        ]);
    }
}

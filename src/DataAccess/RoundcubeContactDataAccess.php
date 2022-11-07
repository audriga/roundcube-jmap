<?php

namespace OpenXPort\DataAccess;

class RoundcubeContactDataAccess extends AbstractDataAccess
{
    // Since list_records() (the Roundcube method we use for reading contacts) requires
    // a max number of contacts to retrieve, we currently supply such a number with the value of 50000
    const NUMBER_OF_CONTACTS_RETRIEVED = 50000;

    private $contact_db;
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $RCMAIL = \rcmail::get_instance(0, $GLOBALS['env']);

        $db = \rcube_db::factory(
            $RCMAIL->config->get('db_dsnw'),
            $RCMAIL->config->get('db_dsnr'),
            $RCMAIL->config->get('db_persistent')
        );

        $userID = $RCMAIL->user->ID;
        $this->contact_db = new \rcube_contacts($db, $userID);
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
            $contactVCard = $c['vcard'];

            $result[$contactId] = $contactVCard;
        }

        // Return the contact IDs and the vCards that we gathered above
        return $result;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
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
                $contactMap[$creationId] = $this->contact_db->insert($rcubeContactToSave);
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
    // TODO support multiple FilterConditions like in JMAP standard
    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}

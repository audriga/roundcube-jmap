<?php

namespace OpenXPort\DataAccess;

class RoundcubeContactDataAccess extends AbstractDataAccess
{
    private $contact_db;

    private function init()
    {
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
        $this->init();

        $contacts = $this->contact_db->list_records(null, 0, true);

        return $contacts;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function create($contactsToCreate, $accountId = null)
    {
        $this->init();

        $contactMap = [];

        foreach ($contactsToCreate as $c) {
            $contactToCreate = reset($c);
            $creationId = key($c);
            $contactMap[$creationId] = $this->contact_db->insert($contactToCreate);
        }

        return $contactMap;
    }

    // Destroys specific entities
    public function destroy($ids, $accountId = null)
    {
        $this->init();

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

<?php

namespace OpenXPort\DataAccess;

class RoundcubeIdentityDataAccess extends AbstractDataAccess
{
    private $account;
    private $logger;

    public function __construct()
    {
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $RCMAIL = \rcmail::get_instance(0, $GLOBALS['env']);

        $this->account = $RCMAIL->user;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting identities");

        $identities = $this->account->list_identities();

        $this->logger->info("Got " . sizeof($identities) . " identities.");

        return $identities;
    }

    public function get($ids, $accountId = null)
    {
        $result = [];
        foreach ($ids as $id) {
            $identity = $this->account->get_identity($id);
            if ($identity) {
                $result[$id] = $identity;
            }
        }
        return $result;
    }

    public function create($identitiesToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($identitiesToCreate) . " identities");

        $identityMap = [];

        foreach ($identitiesToCreate as $i) {
            $identityToCreate = reset($i);
            $creationId = key($i);

            $identityMap[$creationId] = $this->account->insert_identity($identityToCreate);
        }

        return $identityMap;
    }

    // Destroys specific entities
    public function destroy($ids, $accountId = null)
    {
        $destroyed = [];
        foreach ($ids as $id) {
            $this->account->delete_identity($id);
            $destroyed[$id] = 1;
        }
        return $destroyed;
    }

    // Collects multiple ids
    // TODO support multiple FilterConditions like in JMAP standard
    public function query($accountId, $filter = null)
    {
        $ids = [];
        $identities = $this->account->list_identities();
        foreach ($identities as $identity) {
            $ids[] = (string)$identity['identity_id'];
        }
        return ['ids' => $ids];
    }

     /**
     * Update identities
     */
    public function update($entitiesToUpdate, $accountId = null)
    {
        $updated = [];
        foreach ($entitiesToUpdate as $id => $properties) {
            // For now, delete and recreate
            $this->account->delete_identity($id);
            $created = $this->create($properties, $accountId);
            $updated[$id] = $created;
        }
        return $updated;
    }
}

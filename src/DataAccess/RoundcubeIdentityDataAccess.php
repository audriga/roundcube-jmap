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
        throw new BadMethodCallException("Get for specific IDs via Identity/get not implemented.");
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
        throw new BadMethodCallException("Destroy via Identity/set not implemented.");
    }

    // Collects multiple ids
    // TODO support multiple FilterConditions like in JMAP standard
    public function query($accountId, $filter = null)
    {
        throw new BadMethodCallException("Identity/query not implemented.");
    }
}

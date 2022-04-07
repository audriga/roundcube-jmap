<?php

namespace OpenXPort\DataAccess;

class RoundcubeContactGroupDataAccess extends AbstractDataAccess
{
    const NUMBER_OF_CONTACTS_RETRIEVED = 50000;

    private $contact_db;

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
        $this->logger->info("Getting contact groups");

        $contactGroups = $this->contact_db->list_groups(null, 0);

        $this->logger->info("Got " . sizeof($contactGroups) . " contact groups.");

        /**
         * After obtaining contact groups, transform them to contain only the 3 properties "ID", "name"
         * and "contactIds" (which is initially set to an empty array)
         */
        foreach ($contactGroups as $key => $cg) {
            $contactGroups[$key] = array(
                "ID" => $cg['ID'],
                "name" => $cg['name'],
                "contactIds" => []
            );
        }

        /**
         * Get all contacts as well, since we need to add their ids to the groups that they belong to
         */
        $contacts = $this->contact_db->list_records(null, self::NUMBER_OF_CONTACTS_RETRIEVED, true);

        /**
         * For each contact, get a list of all groups the contact belongs to
         * This list is a map of group ID to group name (in PHP: 'group_id' => 'group_name')
         */
        foreach ($contacts as $c) {
            $contactId = $c['ID'];
            $groupsOfContact = $this->contact_db->get_record_groups($contactId);

            /**
             * For each of the fetched contact groups from above, check if its ID is
             * present in the list of group IDs of a given contact.
             * If yes, add the contact's ID to a new property of the group from the
             * fetched list from above, called 'contactIds'. This way we can map 'contactIds'
             * from Roundcube to 'contactIds' in JMAP (See: https://jmap.io/spec-contacts.html#contact-groups)
             */
            foreach ($contactGroups as $key => $cg) {
                if (array_key_exists($cg['ID'], $groupsOfContact)) {
                    array_push($contactGroups[$key]['contactIds'], $contactId);
                }
            }
        }

        return $contactGroups;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function create($contactGroupsToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($contactGroupsToCreate) . " contact groups for user " . $accountId);

        $contactGroupMap = [];

        foreach ($contactGroupsToCreate as $cg) {
            $contactGroupToCreate = reset($cg);
            $creationId = key($cg);

            if (is_null($contactGroupToCreate)) {
                $contactGroupMap[$creationId] = false;
            } else {
                if (isset($contactGroupToCreate['name']) && !empty($contactGroupToCreate['name'])) {
                    $createdGroupRes = $this->contact_db->create_group($contactGroupToCreate['name']);

                    // In case that the result from create_group() is false, then group creation failed
                    if ($createdGroupRes === false) {
                        $contactGroupMap[$creationId] = false;
                    } elseif (is_array($createdGroupRes) && isset($createdGroupRes) && !empty($createdGroupRes)) {
                        // Add all of the group's contacts to the just created group in Roundcube
                        $contactIds = $contactGroupToCreate['contactIds'];
                        if (isset($contactIds) && !empty($contactIds)) {
                            $addToGroupRes = $this->contact_db->add_to_group($createdGroupRes['id'], $contactIds);

                            // add_to_group() returns the number of added contacts
                            // Here we check to see if this number is the same as the size of $contactIds
                            // If it's not, then we consider the contact group creation in Roundcube to have failed
                            if ($addToGroupRes < sizeof($contactIds)) {
                                $contactGroupMap[$creationId] = false;
                            } else {
                                $contactGroupMap[$creationId] = $createdGroupRes['id'];
                            }
                        }
                    }
                }
            }
        }

        return $contactGroupMap;
    }

    public function destroy($ids, $accountId = null)
    {
        $contactGroupMap = [];

        foreach ($ids as $id) {
            $contactGroupMap[$id] = $this->contact_db->delete_group($id);
        }

        return $contactGroupMap;
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}

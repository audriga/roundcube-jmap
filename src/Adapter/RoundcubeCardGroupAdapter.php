<?php

namespace OpenXPort\Adapter;

class RoundcubeCardGroupAdapter extends AbstractAdapter
{
    private $contactGroup;

    public function getContactGroup()
    {
        return $this->contactGroup;
    }

    public function setContactGroup($contactGroup)
    {
        $this->contactGroup = $contactGroup;
    }

    public function getId()
    {
        $contactGroupId = $this->contactGroup['ID'];

        if (!isset($contactGroupId) || empty($contactGroupId)) {
            return null;
        }

        return $contactGroupId;
    }

    public function setId($id)
    {
        if (isset($id) && !empty($id)) {
            $this->contactGroup['ID'] = $id;
        }
    }

    public function getUid()
    {
        // TODO: Implement me (Note: there doesn't seem to exist a counterpart for "uid" in Roundcube's contact groups)
    }

    public function getMembers()
    {
        $contactIds = $this->contactGroup['contactIds'];

        if (!isset($contactIds) || empty($contactIds)) {
            return null;
        }

        $cardGroupMembers = null;

        foreach ($contactIds as $contactId) {
            $cardGroupMembers[$contactId] = true;
        }

        if (isset($cardGroupMembers) && !empty($cardGroupMembers)) {
            return $cardGroupMembers;
        }
    }

    public function setContactIds($members)
    {
        if (isset($members) && !empty($members)) {
            foreach ($members as $contactId => $booleanValue) {
                $this->contactGroup['contactIds'][] = $contactId;
            }
        }
    }

    public function getName()
    {
        $contactGroupName = $this->contactGroup['name'];

        if (!isset($contactGroupName) || empty($contactGroupName)) {
            return null;
        }

        return $contactGroupName;
    }

    public function setName($name)
    {
        if (isset($name) && !empty($name)) {
            $this->contactGroup['name'] = $name;
        }
    }
}

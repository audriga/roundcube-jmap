<?php

namespace OpenXPort\Adapter;

class RoundcubeContactGroupAdapter extends AbstractAdapter
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
        return $this->contactGroup['ID'];
    }

    public function getName()
    {
        return $this->contactGroup['name'];
    }

    public function getContactIds()
    {
        return $this->contactGroup['contactIds'];
    }
}

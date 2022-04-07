<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\JSContact\CardGroup;

class RoundcubeCardGroupMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $creationId => $jsContactCardGroup) {
            $adapter->setId($jsContactCardGroup->id);
            $adapter->setContactIds($jsContactCardGroup->members);
            $adapter->setName($jsContactCardGroup->name);

            array_push($map, array($creationId => $adapter->getContactGroup()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $contactGroup) {
            $adapter->setContactGroup($contactGroup);

            $jmapCardGroup = new CardGroup();

            $jmapCardGroup->setAtType("CardGroup");
            $jmapCardGroup->setId($adapter->getId());
            $jmapCardGroup->setMembers($adapter->getMembers());
            $jmapCardGroup->setName($adapter->getName());

            array_push($list, $jmapCardGroup);
        }

        return $list;
    }
}

<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\AudrigaContact\ContactGroup;

class RoundcubeAudrigaContactGroupMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        // TODO: Implement me
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $cg) {
            $adapter->setContactGroup($cg);

            $jmapContactGroup = new ContactGroup();

            $jmapContactGroup->setId($adapter->getId());
            $jmapContactGroup->setName($adapter->getName());
            $jmapContactGroup->setContactIds($adapter->getContactIds());

            array_push($list, $jmapContactGroup);
        }

        return $list;
    }
}

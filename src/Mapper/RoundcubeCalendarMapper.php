<?php

namespace OpenXPort\Mapper;

use OpenXPort\Mapper\AbstractMapper;
use OpenXPort\Jmap\Calendar\Calendar;

class RoundcubeCalendarMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        // TODO: Implement me
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $calendar) {
            $adapter->setCalendar($calendar);

            $jmapCalendar = new Calendar();
            $jmapCalendar->setId($adapter->getId());
            $jmapCalendar->setName($adapter->getName());
            $jmapCalendar->setColor($adapter->getColor());
            $jmapCalendar->setIsVisible($adapter->getIsVisible());
            $jmapCalendar->setRole($adapter->getRole());

            array_push($list, $jmapCalendar);
        }

        return $list;
    }
}

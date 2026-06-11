<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Calendar\Calendar;

class RoundcubeCalendarMapper
{
    public function mapToJmap($data, $adapter = null)
    {
        $list = [];
        foreach ($data as $id => $row) {
            $calendar = new Calendar();
            $calendar->setId((string)$id);
            $calendar->setName($row['name']);
            $calendar->setColor('#' . $row['color']);
            $calendar->setIsVisible(true);
            $calendar->setIsSubscribed(true);
            $list[] = $calendar;
        }
        return $list;
    }

    public function mapFromJmap($data, $adapter = null)
    {
        $result = [];
        foreach ($data as $creationId => $calendar) {
            $calArray = is_object($calendar) ? json_decode(json_encode($calendar), true) : $calendar;
            $result[$creationId] = [
                'name'  => $calArray['name'] ?? 'New Calendar',
                'color' => $calArray['color'] ?? '#cc0000',
            ];
        }
        return $result;
    }
}

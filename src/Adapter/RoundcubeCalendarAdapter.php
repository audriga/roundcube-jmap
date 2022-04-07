<?php

namespace OpenXPort\Adapter;

class RoundcubeCalendarAdapter extends AbstractAdapter
{
    private $calendar;

    public function getCalendar()
    {
        return $this->calendar;
    }

    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
    }

    public function getId()
    {
        return $this->calendar['id'];
    }

    public function getName()
    {
        return $this->calendar['name'];
    }

    public function getColor()
    {
        return $this->calendar['color'];
    }

    public function getIsVisible()
    {
        $active = $this->calendar['active'];

        if (isset($active) && !is_null($active)) {
            return $active;
        }
    }

    public function getRole()
    {
        $name = $this->calendar['name'];

        if (isset($name) && !is_null($name) && \strcmp($name, "Default") === 0) {
            return "inbox";
        }

        return null;
    }
}

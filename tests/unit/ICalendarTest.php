<?php

namespace OpenXPort\Test\ICalendar;

use PHPUnit\Framework\TestCase;

final class ICalendarTest extends TestCase
{
    /**
     * @var ZCiCal
     */
    protected $iCalendarOneAttendee = null;

    /**
     * @var ZCiCal
     */
    protected $iCalendarTwoAttendees = null;

    /**
     * @var RoundcubeCalendarEventAdapter
     */
    protected $adapter = null;

    public function setUp(): void
    {
        require_once __DIR__ . '/../../icalendar/zapcallib.php';
        $iCalendarStringOneAttendee = file_get_contents(__DIR__ . '/../data/event-with-organizer-and-one-attendee.ics');
        $this->iCalendarOneAttendee = new \ZCiCal($iCalendarStringOneAttendee);
        $iCalendarStringTwoAttendees = file_get_contents(
            __DIR__ . '/../data/event-with-organizer-and-two-attendees.ics'
        );
        $this->iCalendarTwoAttendees = new \ZCiCal($iCalendarStringTwoAttendees);
        $this->adapter = new \OpenXPort\Adapter\RoundcubeCalendarEventAdapter();
    }

    public function tearDown(): void
    {
        $this->iCalendarOneAttendee = null;
        $this->adapter = null;
    }

    public function testReadOneAttendeeAndOrganizerFromICalendar()
    {
        $this->adapter->setICalEvent($this->iCalendarOneAttendee->curnode->child[0]);
        $iCalendarAttendees = $this->adapter->getParticipants();
        $this->assertCount(2, $iCalendarAttendees, "Event doesn't containt one organizer and one attendee");
        $iCalendarAttendee = $iCalendarAttendees[array_key_first($iCalendarAttendees)];
        $iCalendarOrganizer = $iCalendarAttendees[array_key_last($iCalendarAttendees)];
        $this->assertArrayHasKey("attendee", $iCalendarAttendee->getRoles());
        $this->assertArrayHasKey("owner", $iCalendarOrganizer->getRoles());
    }

    public function testReadTwoAttendeesAndOrganizerFromICalendar()
    {
        $this->adapter->setICalEvent($this->iCalendarTwoAttendees->curnode->child[0]);
        $iCalendarAttendees = $this->adapter->getParticipants();
        $this->assertCount(3, $iCalendarAttendees, "Event doesn't containt one organizer and one attendee");
        $iCalendarAttendee1 = $iCalendarAttendees[array_keys($iCalendarAttendees)[0]];
        $iCalendarAttendee2 = $iCalendarAttendees[array_keys($iCalendarAttendees)[1]];
        $iCalendarOrganizer = $iCalendarAttendees[array_key_last($iCalendarAttendees)];
        $this->assertArrayHasKey("attendee", $iCalendarAttendee1->getRoles());
        $this->assertArrayHasKey("attendee", $iCalendarAttendee2->getRoles());
        $this->assertArrayHasKey("owner", $iCalendarOrganizer->getRoles());
    }
}

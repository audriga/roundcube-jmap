===================================
Roundcube JMAP Plugin Release Notes
===================================

.. contents:: Topics

v1.2.1
=======

Release Summary
---------------
Fix byDay recurrence rule ( #5777 )

Details
-------
* Calendars: Fix byDay recurrence rule ( #5777 )

v1.2.0
=======

Release Summary
---------------
Next generation logging and configuration.

Details
-------
* Move log initialization to OXP
* Next-generation config file with defaults if nothing configured
* Support Graylog
* Calendars: Use own mirror of icalendar library ( #5716 )
* Contacts: Try to flatten non-arrays when parsing participants from iCalendar ( #5727 )

v1.1.2
=======

Release Summary
---------------
Fixes minor write issues

Details
-------
* Depend on OXP version 1
* Contacts: Fix some write issues

v0.12.3
=======

Release Summary
---------------
Hotfix release for Roundcube

Details
-------
* Calendars: Handle all escape chars #5716
* Calendars: Also export events with a single attendee #5727 (regression of #5476)

v0.9.0
======

Release Summary
---------------
Fixes several calendar issues

Details
-------
* Calendar: Support negative values of byDay #5438
* Calendar: Fix fullDay until reccurenceRule #5447

v0.8.0
======

Release Summary
---------------
Supports some cPanel weirdness

Details
-------
* Calendar: Fix modified exceptions for fullDay events #5414
* Calendar: Support custom cPanel API #5433
* Contacts: Set maxObjectsInGet to 50000 from 5000 #5421

v0.7.0
======

Release Summary
---------------
Various fixes.

Details
-------
* Calendar: Fix modified exceptions in the recurrenceOverrides property of events
* Calendar: Export attachments
* Calendar: Add fix for deleted exceptions

v0.6.0
======

Release Summary
---------------
Fixes a critical bug

Details
-------
* Do not define visibility for constant

v0.5.0
======

Release Summary
---------------
Adds more contact/calendar features and uses a single folder everywhere

Details
-------
* Place files under plugins folder only
* Explicitly include libcalendaring
* Add more calendar properties #5372

v0.4.0
======

Release Summary
---------------
Allow debug output in API and add some folders.

Details
-------
* Print debug logs via API (to debug Error 500)
* Contact group support
* Bring back Identity support
* Calendar folder support
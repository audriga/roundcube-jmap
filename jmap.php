<?php

use OpenXPort\Jmap\Contact\ContactsAccountCapability;
use OpenXPort\Jmap\Core\CoreAccountCapability;
use OpenXPort\Jmap\Mail\SubmissionAccountCapability;
use OpenXPort\Util\RoundcubeSessionUtil;

// Define version
$oxpVersion = '1.4.0';

/**
 * Fix for a refactoring bug (due to usage of bridge.php)
 *
 * The problem is that $_SERVER['SCRIPT_FILENAME'] is used for setting the include_path for Roundcube,
 * but it references the currently executed script, which is jmap.php in our case.
 * Since jmap.php is not positioned as a file on the same level as index.php,
 * which is normally the running script, the include_path of Roundcube gets messed up.
 * That's why we have to explicitly hack $_SERVER['SCRIPT_FILENAME'] so roundcube gets the correct
 * include_path.
 * For more info, see: https://github.com/roundcube/roundcubemail/blob/master/program/include/iniset.php
 * (lines 27, 47 and 48)
 */

$_SERVER['SCRIPT_FILENAME'] = realpath(__DIR__ . '/../../index.php');

/* START OF OPENXPORT Code only */
// Use our composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Build config
$configDefault = include(__DIR__ . '/config/config.default.php');
$configFile = __DIR__ . '/config/config.php';
$oxpConfig = $configDefault;

if (file_exists($configFile)) {
    $configUser = include($configFile);
    if (is_array($configUser)) {
        $oxpConfig = array_merge($configDefault, $configUser);
    }
};

// Handle errors and exceptions as JSON responses
$handler = new \OpenXPort\Jmap\Core\ErrorHandler($oxpConfig["verboseErrorOutput"]);
$handler->setHandlers();

// Decode JSON post body here in case the debug capability is included
$jmapRequest = OpenXPort\Util\HttpUtil::getRequestBody();

// Initialize logging
OpenXPort\Util\Logger::init($oxpConfig, $jmapRequest);
$logger = \OpenXPort\Util\Logger::getInstance();

// Reuse auth from webmailer
require_once __DIR__ . '/bridge.php';

$logger->notice("Running PHP v" . phpversion() . ", RC v" . RCMAIL_VERSION . ", Plugin v" . $oxpVersion);

// TODO Probably from here on only
$accessors = array(
    "AddressBooks" => new \OpenXPort\DataAccess\RoundcubeAddressBookDataAccess(),
    "ContactCard" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "Calendars" => null,
    "CalendarEvents" => new \OpenXPort\DataAccess\RoundcubeCalendarEventDataAccess(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\DataAccess\RoundcubeIdentityDataAccess(),
    "Filters" => null,
    "StorageNodes" => null,
);

/**
 * Array to hold adapter classes for different types of data
 * "null" means that no adapter class is present/available for the given data type
*/
$adapters = array(
    "AddressBooks" => null,
    "Calendars" => null,
    "CalendarEvents" => new \OpenXPort\Adapter\RoundcubeJSCalendarICalendarAdapter(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Adapter\RoundcubeIdentityAdapter(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactCard" => new \OpenXPort\Adapter\RoundcubeJSContactVCardAdapter(
        $oxpConfig['vCardParsing'],
        $oxpConfig['dumpInvalidVCards']
    ),
);

/**
 * Array to hold mapper classes for different types of data
 * "null" means that no mapper class is present/available for the given data type
*/
$mappers = array(
    "AddressBooks" => new \OpenXPort\Mapper\RoundcubeAddressBookMapper(),
    "Calendars" => null,
    "CalendarEvents" => new \OpenXPort\Mapper\RoundcubeJSCalendarICalendarMapper(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Mapper\RoundcubeIdentityMapper(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactCard" => new \OpenXPort\Mapper\RoundcubeJSContactVCardMapper(),
);

$accountData = [
    'accountId' => $RCMAIL->user->ID,
    'username' => isset($users[1]) ? $users[1] : $_POST['_user'],
    'accountCapabilities' => []
];
$session = RoundcubeSessionUtil::createSession($accountData);

$server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers, $oxpConfig, $session);
$server->handleJmapRequest($jmapRequest);

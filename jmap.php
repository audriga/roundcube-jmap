<?php

use OpenXPort\Util\RoundcubeSessionUtil;

// Define version
$oxpVersion = '1.4.1';

// Use OXP composer autoload
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

// Initialize Webmailer
require_once __DIR__ . '/bridge.php';

$logger->notice("Running PHP v" . phpversion() . ", RC v" . RCMAIL_VERSION . ", Plugin v" . $oxpVersion);

$accessors = array(
    "AddressBooks" => new \OpenXPort\DataAccess\RoundcubeAddressBookDataAccess(),
    "ContactCard" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "Calendars" => new \OpenXPort\DataAccess\RoundcubeCalendarDataAccess(),
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
    "Calendars" => new \OpenXPort\Mapper\RoundcubeCalendarMapper(),
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
    'username' => isset($users[1]) ? $users[1] : $user,
    'accountCapabilities' => []
];
$session = RoundcubeSessionUtil::createSession($accountData);

$server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers, $oxpConfig, $session);
$server->handleJmapRequest($jmapRequest);

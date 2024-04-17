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
    "Contacts" => null,
    "Calendars" => null,
    "CalendarEvents" => null,
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\DataAccess\RoundcubeIdentityDataAccess(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => null,
    "Cards" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "CardGroups" => new \OpenXPort\DataAccess\RoundcubeContactGroupDataAccess(),
);

/**
 * Array to hold adapter classes for different types of data
 * "null" means that no adapter class is present/available for the given data type
*/
$adapters = array(
    "Contacts" => null,
    "Calendars" => null,
    "CalendarEvents" => null,
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Adapter\RoundcubeIdentityAdapter(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => null,
    "Cards" => new \OpenXPort\Adapter\RoundcubeJSContactVCardAdapter(
        $oxpConfig['vCardParsing'],
        $oxpConfig['dumpInvalidVCards']
    ),
    "CardGroups" => new \OpenXPort\Adapter\RoundcubeCardGroupAdapter()
);

/**
 * Array to hold mapper classes for different types of data
 * "null" means that no mapper class is present/available for the given data type
*/
$mappers = array(
    "Contacts" => null,
    "Calendars" => null,
    "CalendarEvents" => null,
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Mapper\RoundcubeIdentityMapper(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => null,
    "Cards" => new \OpenXPort\Mapper\RoundcubeJSContactVCardMapper(),
    "CardGroups" => new \OpenXPort\Mapper\RoundcubeCardGroupMapper()
);

$accountData = [
    'accountId' => $RCMAIL->user->ID,
    'username' => isset($users[1]) ? $users[1] : $user,
    'accountCapabilities' => []
];
$session = RoundcubeSessionUtil::createSession($accountData);

$server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers, $oxpConfig, $session);
$server->handleJmapRequest($jmapRequest);

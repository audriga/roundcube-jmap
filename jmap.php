<?php

// Define version
$oxpVersion = '1.1.1';

/**
 * Fix for a refactoring bug (due to usage of bridge.php)
 *
 * The problem is that $_SERVER['SCRIPT_FILENAME'] is used for setting the include_path for Roundcube,
 * but it references the currently executed script, which is jmap.php in our case.
 * Since jmap.php is not positioned as a file on the same level as index.php,
 * which is normally the running script, the include_path of Roundcube gets messed up.
 * That's why we have to explicitly set $_SERVER['SCRIPT_FILENAME'] to a correct value here,
 * in order to not mess up the indclude_path.
 * For more info, see: https://github.com/roundcube/roundcubemail/blob/master/program/include/iniset.php
 * (lines 27, 47 and 48)
 */

$_SERVER['SCRIPT_FILENAME'] = dirname($_SERVER['SCRIPT_FILENAME'], 2);

/* START OF OPENXPORT Code only */
// Use our composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Print debug output via API on error
// NOTE: Do not use on public-facing setups
$handler = new \OpenXPort\Jmap\Core\ErrorHandler();
$handler->setHandlers();

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
    "Contacts" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "Calendars" => new \OpenXPort\DataAccess\RoundcubeCalendarDataAccess(),
    "CalendarEvents" => new \OpenXPort\DataAccess\RoundcubeCalendarEventDataAccess(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\DataAccess\RoundcubeIdentityDataAccess(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => new \OpenXPort\DataAccess\RoundcubeContactGroupDataAccess(),
    "Cards" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "CardGroups" => new \OpenXPort\DataAccess\RoundcubeContactGroupDataAccess(),
);

/**
 * Array to hold adapter classes for different types of data
 * "null" means that no adapter class is present/available for the given data type
*/
$adapters = array(
    "Contacts" => new \OpenXPort\Adapter\RoundcubeVCardAdapter(),
    "Calendars" => new \OpenXPort\Adapter\RoundcubeCalendarAdapter(),
    "CalendarEvents" => new \OpenXPort\Adapter\RoundcubeCalendarEventAdapter(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Adapter\RoundcubeIdentityAdapter(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => new \OpenXPort\Adapter\RoundcubeContactGroupAdapter(),
    "Cards" => new \OpenXPort\Adapter\RoundcubeJSContactVCardAdapter(),
    "CardGroups" => new \OpenXPort\Adapter\RoundcubeCardGroupAdapter()
);

/**
 * Array to hold mapper classes for different types of data
 * "null" means that no mapper class is present/available for the given data type
*/
$mappers = array(
    "Contacts" => new \OpenXPort\Mapper\RoundcubeVCardMapper(),
    "Calendars" => new \OpenXPort\Mapper\RoundcubeCalendarMapper(),
    "CalendarEvents" => new \OpenXPort\Mapper\RoundcubeCalendarEventMapper(),
    "Tasks" => null,
    "Notes" => null,
    "Identities" => new \OpenXPort\Mapper\RoundcubeIdentityMapper(),
    "Filters" => null,
    "StorageNodes" => null,
    "ContactGroups" => new \OpenXPort\Mapper\RoundcubeContactGroupMapper(),
    "Cards" => new \OpenXPort\Mapper\RoundcubeJSContactVCardMapper(),
    "CardGroups" => new \OpenXPort\Mapper\RoundcubeCardGroupMapper()
);

$server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers, $oxpConfig);
$server->handleJmapRequest($jmapRequest);

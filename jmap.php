<?php
/* START OF OPENXPORT Code only */
// Reuse auth from webmailer
require_once __DIR__ . '/mailer.php';

// Use our composer autoload
require_once('plugins/jmap/vendor/autoload.php');

// TODO Probably from here on only
$accessors = array(
    "Contacts" => new \OpenXPort\DataAccess\RoundcubeContactDataAccess(),
    "Calendars" => null,
    "Tasks" => null,
    "Notes" => null,
    "Settings" => null, // new \OpenXPort\DataAccess\RoundcubeIdentityDataAccess(),
    "Filters" => null,
    "Files" => null
);

/**
 * Array to hold adapter classes for different types of data
 * "null" means that no adapter class is present/available for the given data type
*/
$adapters = array(
    "Contacts" => new \OpenXPort\Adapter\RoundcubeContactAdapter(),
    "Calendars" => null,
    "Tasks" => null,
    "Notes" => null,
    "Settings" => null, //new RoundcubeIdentityAdapter(),
    "Filters" => null,
    "Files" => null
);

/**
 * Array to hold mapper classes for different types of data
 * "null" means that no mapper class is present/available for the given data type
*/
$mappers = array(
    "Contacts" => new \OpenXPort\Mapper\RoundcubeContactMapper(),
    "Calendars" => null,
    "Tasks" => null,
    "Notes" => null,
    "Settings" => null, //new RoundcubeIdentityMapper(),
    "Filters" => null,
    "Files" => null
);

$server = new \Jmap\Core\Server($accessors, $adapters, $mappers);
$server->listen();

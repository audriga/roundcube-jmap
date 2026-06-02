<?php
use OpenXPort\Jmap\Contact\ContactsAccountCapability;
use OpenXPort\Jmap\Core\CoreAccountCapability;
use OpenXPort\Jmap\Mail\SubmissionAccountCapability;
use OpenXPort\Util\RoundcubeSessionUtil;

$oxpVersion = "1.4.0";
$_SERVER["SCRIPT_FILENAME"] = realpath(__DIR__ . "/../../index.php");

require_once __DIR__ . "/vendor/autoload.php";

$configDefault = include(__DIR__ . "/config/config.default.php");
$configFile = __DIR__ . "/config/config.php";
$oxpConfig = $configDefault;
if (file_exists($configFile)) {
    $configUser = include($configFile);
    if (is_array($configUser)) {
        $oxpConfig = array_merge($configDefault, $configUser);
    }
}

$handler = new \OpenXPort\Jmap\Core\ErrorHandler($oxpConfig["verboseErrorOutput"]);
$handler->setHandlers();

$jmapRequest = OpenXPort\Util\HttpUtil::getRequestBody();
OpenXPort\Util\Logger::init($oxpConfig, $jmapRequest);

// BYPASS AUTH - Create fake account data
$accountData = [
    "username" => "testuser@local",
    "accountId" => "testuser@local",
    "accountCapabilities" => [
        new ContactsAccountCapability()
    ]
];

$session = RoundcubeSessionUtil::createSession($accountData);

// Continue with rest of jmap.php logic...
require_once __DIR__ . "/../../program/include/iniset.php";
$RCMAIL = rcmail::get_instance(0, $GLOBALS["env"]);

// Set fake user session
$_SESSION["user_id"] = 3;
$_SESSION["username"] = "testuser@local";

include(__DIR__ . "/../../vendor/audriga/jmap-openxport/src/Jmap/Core/Server.php");

// The rest would need the full jmap.php code...
echo json_encode(["status" => "test mode active"]);
?>

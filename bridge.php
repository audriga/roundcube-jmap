<?php
// Assuming we are inside RC's plugins/jmap dir
define('INSTALL_PATH', realpath('../../') . '/');

// load the whole Roundcube Webmail code with its autoloader
require_once INSTALL_PATH . '/program/include/iniset.php';
$RCMAIL = rcmail::get_instance(rcube::INIT_WITH_DB | rcube::INIT_WITH_PLUGINS);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

/// Impersonation / admin auth BEGIN
// An array to store the admin user, as well the user-to-impersonate
// (in case of admin auth)
$users = [];

// Check if we're dealing with admin auth credentials
// and if yes, then take the first part as the admin username
// to use for login
if (mb_strpos($user, "*")) {
    $users = explode("*", $user);
    $user = $users[0];
}

/// Authenticate hook
$pass_charset  = $RCMAIL->config->get('password_charset', 'UTF-8');

$auth = $RCMAIL->plugins->exec_hook('authenticate', array(
    'host'  => $RCMAIL->autoselect_host(),
    'user'  => trim(rcube_utils::parse_input_value($user)),
    'pass'  => rcube_utils::parse_input_value($pass, true, $pass_charset),
    'valid' => true, //  It is always valid in Karlsruhe!
    'cookiecheck' => false, // No cookies for you in Karlsruhe!
));

// IMAP Login
$login_success = false;
if ($auth['valid'] && !$auth['abort']){
    if($RCMAIL->login($auth['user'], $auth['pass'], $auth['host'], false, true)) {
        $logger->info("Successfully logged in as " . $auth['user']);
        $login_success = true;
    }
}
if (!$auth['valid'] || $auth['abort'] || !$login_success){
    if (!$auth['valid']) {
        $error_code = rcmail::ERROR_INVALID_REQUEST;
    } else {
        $error_code = is_numeric($auth['error']) ? $auth['error'] : $RCMAIL->login_error();
    }
    $error_labels = array(
        rcmail::ERROR_STORAGE          => 'storageerror',
        rcmail::ERROR_COOKIES_DISABLED => 'cookiesdisabled',
        rcmail::ERROR_INVALID_REQUEST  => 'invalidrequest',
        rcmail::ERROR_INVALID_HOST     => 'invalidhost',
        rcmail::ERROR_RATE_LIMIT       => 'accountlocked',
    );

    $error_message = !empty($auth['error']) && !is_numeric($auth['error'])
        ? $auth['error'] : ($error_labels[$error_code] ?: 'loginfailed');

    // log failed login
    $RCMAIL->log_login($auth['user'], true, $error_code);

    $logger->error("Failed log in for " . $auth['user'] . " error message is " . $error_message);

    // Return auth error via API
    $loginError = null;

    switch ($error_code) {
    case rcmail::ERROR_RATE_LIMIT:
        $loginError = 'urn:ietf:params:jmap:error:limit';
        header('HTTP/1.0 429 Too Many Requests');
        break;
    case rcmail::ERROR_INVALID_REQUEST:
        $loginError = 'urn:ietf:params:jmap:error:notRequest';
        header('HTTP/1.0 400 Bad Request');
        break;
    default:
        $loginError = '401 Unauthorized';
        header('HTTP/1.0 401 Unauthorized');
    }

    die($loginError);
}

/// Impersonation / admin auth APPENDIX
// Obtain the username of the user that we want to act on behalf of
if (isset($users[1]) && !empty($users[1])) {
    if (!in_array($users[0], $oxpConfig["adminUsers"])) {
        http_response_code(403);
        die("403 Forbidden");
    }

    // Try to get the user that corresponds to this username via the rcube_user::query() method
    // Since query() requires the user's domain as the second parameter, we take the domain of the logged-in user
    // (in admin auth scenario, that is the admin user). We assume for admin auth that both admin
    // and user-acted-on-behalf-of share the same domain
    $userDomain = $RCMAIL->user->get_username('domain');
    $userToSetAsCurrentUser = rcube_user::query($users[1], $userDomain);

    // If we managed to get this user, then we set this user as the current user within Roundcube via set_user()
    if (isset($userToSetAsCurrentUser) && !empty($userToSetAsCurrentUser)) {
        $RCMAIL->set_user($userToSetAsCurrentUser);
    }
}

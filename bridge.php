<?php
// Parts of this file are based on index.php (Roundcube version 1.4.8).
// TODO Reduce amount of duplicate code from index.php. We may be able to do that by:
//  * removing authenticate hook logic using $_POST.
//  * moving login logic to a function provided by base Roundcube

// include environment
require_once __DIR__ . '/../../program/include/iniset.php';

// init application, start session, init output class, etc.
$RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);

/// Auth hack BEGIN
// TODO authenticate hook may actually be removed. Unclear if this is required for cPanel auth.
// Set some global POST vars that would be usually set via HTML <input> tags are:
// _task, _action, _timezone, _user, _pass, _token . We set all except for token.
// Token should only be required for an existing session. Also disregarding Timezone for now
$_POST['_user'] = $_SERVER['PHP_AUTH_USER'];
$_POST['_pass'] = $_SERVER['PHP_AUTH_PW'];
$_POST['_action'] = 'login';
$_POST['_task'] = 'login';

/// Impersonation / admin auth BEGIN
// An array to store the admin user, as well the user-to-impersonate
// (in case of admin auth)
$users = [];

// Check if we're dealing with admin auth credentials
// and if yes, then take the first part as the admin username
// to use for login
if (mb_strpos($_POST['_user'], "*")) {
    $users = explode("*", $_POST['_user']);
    $_POST['_user'] = $users[0];
}
/// Impersonation / admin auth END

$pass_charset  = $RCMAIL->config->get('password_charset', 'UTF-8');

$auth = $RCMAIL->plugins->exec_hook('authenticate', array(
    'host'  => $RCMAIL->autoselect_host(),
    'user'  => trim(rcube_utils::get_input_value('_user', rcube_utils::INPUT_POST)),
    'pass'  => rcube_utils::get_input_value('_pass', rcube_utils::INPUT_POST, true, $pass_charset),
    'valid' => true, //  It is always valid in Karlsruhe!
    'cookiecheck' => false, // No cookies for you in Karlsruhe!
));
/// Auth hack END

// Login
// TODO The following contains quite a lot of duplicate code from RC's index.php.
//   It may be moved to an own function (except for returning errors via API)?
if (
    $auth['valid'] && !$auth['abort']
    && $RCMAIL->login($auth['user'], $auth['pass'], $auth['host'], $auth['cookiecheck'])
) {
    $logger->info("Successfully logged in as " . $auth['user']);

    // log successful login
    $RCMAIL->log_login();
} else {
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

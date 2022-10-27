<?php

/* Copied and edited code from index.php (Roundcube version 1.4.8) */
// include environment
require_once __DIR__ . '/../../program/include/iniset.php';

// init application, start session, init output class, etc.
$RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);

// Make the whole PHP output non-cacheable (#1487797)
$RCMAIL->output->nocacheing_headers();
$RCMAIL->output->common_headers(!empty($_SESSION['user_id']));

// turn on output buffering
ob_start();

// check if config files had errors
if ($err_str = $RCMAIL->config->get_error()) {
    rcmail::raise_error(array(
        'code' => 601,
        'type' => 'php',
        'message' => $err_str), false, true);
}

// check DB connections and exit on failure
if ($err_str = $RCMAIL->db->is_error()) {
    rcmail::raise_error(array(
        'code' => 603,
        'type' => 'db',
        'message' => $err_str), false, true);
}

// error steps
// OpenXPort: Task is always assumed to be login
// Removed

// check if https is required (for login) and redirect if necessary
// OpenXPort: TODO does that make sense?
if (empty($_SESSION['user_id']) && ($force_https = $RCMAIL->config->get('force_https', false))) {
    // force_https can be true, <hostname>, <hostname>:<port>, <port>
    if (!is_bool($force_https)) {
        list($host, $port) = explode(':', $force_https);

        if (is_numeric($host) && empty($port)) {
            $port = $host;
            $host = '';
        }
    }

    if (!rcube_utils::https_check($port ?: 443)) {
        if (empty($host)) {
            $host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);
        }
        if ($port && $port != 443) {
            $host .= ':' . $port;
        }

        header('Location: https://' . $host . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// trigger startup plugin hook
$startup = $RCMAIL->plugins->exec_hook('startup', array('task' => $RCMAIL->task, 'action' => $RCMAIL->action));
$RCMAIL->set_task($startup['task']);
$RCMAIL->action = $startup['action'];

// try to log in
if (true) { // OpenXPort: Task is always assumed to be login
    $request_valid = true; // OpenXPort: It is always valid in Karlsruhe!
    $pass_charset  = $RCMAIL->config->get('password_charset', 'UTF-8');

    // purge the session in case of new login when a session already exists
    // OpenXPort: Disregard Session
    // Removed

    // OpenXPort: Auth hack
    // Set some global POST vars that would be usually set via HTML <input> tags are:
    // _task, _action, _timezone, _user, _pass, _token . We set all except for token.
    // Token should only be required for an existing session.
    // Disregarding Timezone for now
    $_POST['_user'] = $_SERVER['PHP_AUTH_USER'];
    $_POST['_pass'] = $_SERVER['PHP_AUTH_PW'];
    $_POST['_action'] = 'login';
    $_POST['_task'] = 'login';

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

    // TODO: Find a way to set the user with the provided username
    // for impersonation as the current user

    $auth = $RCMAIL->plugins->exec_hook('authenticate', array(
            'host'  => $RCMAIL->autoselect_host(),
            'user'  => trim(rcube_utils::get_input_value('_user', rcube_utils::INPUT_POST)),
            'pass'  => rcube_utils::get_input_value('_pass', rcube_utils::INPUT_POST, true, $pass_charset),
            'valid' => $request_valid,
            'cookiecheck' => false, // OpenXPort: No cookies for you in Karlsruhe!
    ));

    // Login
    if (
        $auth['valid'] && !$auth['abort']
        && $RCMAIL->login($auth['user'], $auth['pass'], $auth['host'], $auth['cookiecheck'])
    ) {
        // create new session ID, don't destroy the current session
        // it was destroyed already by $RCMAIL->kill_session() above
        // OpenXPort: Disregard Session
        // Removed

        $logger->info("Successfully logged in as " . $auth['user']);

        // log successful login
        $RCMAIL->log_login();

        // restore original request parameters
        // OpenXPort: Task is always assumed to be login
        // Removed

        // allow plugins to control the redirect url after login success
        // OpenXPort: Task is always assumed to be login
        // Removed

        // send redirect
        // OpenXPort: Task is always assumed to be login
        // Removed
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

        // OpenXPort: We do our own presentation
        // Removed

        // log failed login
        $RCMAIL->log_login($auth['user'], true, $error_code);

        $logger->error("Failed log in for " . $auth['user'] . " error message is " . $error_message);

        // OpenXPort: Return auth error via API
        $loginError = null;

        // OpenXPort: TODO use http_response_code() once we move that to generic lib.
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

        $RCMAIL->plugins->exec_hook('login_failed', array(
            'code' => $error_code, 'host' => $auth['host'], 'user' => $auth['user']));

        if (!isset($_SESSION['user_id'])) {
            $RCMAIL->kill_session();
        }
    }
}

// end session
// OpenXPort: Task is always assumed to be login
// Removed

// check session and auth cookie
// OpenXPort: Task is always assumed to be login
// Removed

// not logged in -> show login page
// OpenXPort: Task is always assumed to be login
// Removed

// we're ready, user is authenticated and the request is safe
$plugin = $RCMAIL->plugins->exec_hook('ready', array('task' => $RCMAIL->task, 'action' => $RCMAIL->action));
$RCMAIL->set_task($plugin['task']);
$RCMAIL->action = $plugin['action'];

// handle special actions
// OpenXPort: Task is always assumed to be login
// Removed


// include task specific functions
if (is_file($incfile = INSTALL_PATH . 'program/steps/' . $RCMAIL->task . '/func.inc')) {
    include_once $incfile;
}

// allow 5 "redirects" to another action
// OpenXPort: Task is always assumed to be login
// Removed

// OpenXPort: Task is always assumed to be login
// Removed

// parse main template (default)
// OpenXPort: We do our own presentation
// Removed

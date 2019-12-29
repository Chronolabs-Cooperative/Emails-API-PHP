<?php
/**
 +-------------------------------------------------------------------------+
 | Chronomail Webmail IMAP Client                                           |
 | Version 1.4.1                                                           |
 |                                                                         |
 | Copyright (C) The Chronomail Dev Team                                    |
 |                                                                         |
 | This program is free software: you can redistribute it and/or modify    |
 | it under the terms of the GNU General Public License (with exceptions   |
 | for skins & plugins) as published by the Free Software Foundation,      |
 | either version 3 of the License, or (at your option) any later version. |
 |                                                                         |
 | This file forms part of the Chronomail Webmail Software for which the    |
 | following exception is added: Plugins and Skins which merely make       |
 | function calls to the Chronomail Webmail Software, and for that purpose  |
 | include it by reference shall not be considered modifications of        |
 | the software.                                                           |
 |                                                                         |
 | If you wish to use this file in another project or create a modified    |
 | version that will not be part of the Chronomail Webmail Software, you    |
 | may remove the exception above and use this source code under the       |
 | original version of the license.                                        |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            |
 | GNU General Public License for more details.                            |
 |                                                                         |
 | You should have received a copy of the GNU General Public License       |
 | along with this program.  If not, see http://www.gnu.org/licenses/.     |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                          |
 | Author: Aleksander Machniak <alec@alec.pl>                              |
 +-------------------------------------------------------------------------+
*/

// include environment
require_once __DIR__ . DIRECTORY_SEPARATOR . 'apiconfig.php';
require_once 'program/include/iniset.php';

// init application, start session, init output class, etc.
$Chronomail = chronomail::get_instance(0, $GLOBALS['env']);

// Make the whole PHP output non-cacheable (#1487797)
$Chronomail->output->nocacheing_headers();
$Chronomail->output->common_headers(!empty($_SESSION['user_id']));

// turn on output buffering
ob_start();

// check if config files had errors
if ($err_str = $Chronomail->config->get_error()) {
    chronomail::raise_error(array(
        'code' => 601,
        'type' => 'php',
        'message' => $err_str), false, true);
}

// check DB connections and exit on failure
if ($err_str = $Chronomail->db->is_error()) {
    chronomail::raise_error(array(
        'code' => 603,
        'type' => 'db',
        'message' => $err_str), false, true);
}

// error steps
if ($Chronomail->action == 'error' && !empty($_GET['_code'])) {
    chronomail::raise_error(array('code' => hexdec($_GET['_code'])), false, true);
}

// check if https is required (for login) and redirect if necessary
if (empty($_SESSION['user_id']) && ($force_https = $Chronomail->config->get('force_https', false))) {
    // force_https can be true, <hostname>, <hostname>:<port>, <port>
    if (!is_bool($force_https)) {
        list($host, $port) = explode(':', $force_https);

        if (is_numeric($host) && empty($port)) {
            $port = $host;
            $host = '';
        }
    }

    if (!chronomail_utils::https_check($port ?: 443)) {
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
$startup = $Chronomail->plugins->exec_hook('startup', array('task' => $Chronomail->task, 'action' => $Chronomail->action));
$Chronomail->set_task($startup['task']);
$Chronomail->action = $startup['action'];

// try to log in
if ($Chronomail->task == 'login' && $Chronomail->action == 'login') {
    $request_valid = $_SESSION['temp'] && $Chronomail->check_request();
    $pass_charset  = $Chronomail->config->get('password_charset', 'UTF-8');

    // purge the session in case of new login when a session already exists
    $Chronomail->kill_session();

    $auth = $Chronomail->plugins->exec_hook('authenticate', array(
            'host'  => $Chronomail->autoselect_host(),
            'user'  => trim(chronomail_utils::get_input_value('_user', chronomail_utils::INPUT_POST)),
            'pass'  => chronomail_utils::get_input_value('_pass', chronomail_utils::INPUT_POST, true, $pass_charset),
            'valid' => $request_valid,
            'cookiecheck' => true,
    ));

    // Login
    if ($auth['valid'] && !$auth['abort']
        && $Chronomail->login($auth['user'], $auth['pass'], $auth['host'], $auth['cookiecheck'])
    ) {
        // create new session ID, don't destroy the current session
        // it was destroyed already by $Chronomail->kill_session() above
        $Chronomail->session->remove('temp');
        $Chronomail->session->regenerate_id(false);

        // send auth cookie if necessary
        $Chronomail->session->set_auth_cookie();

        // log successful login
        $Chronomail->log_login();

        // restore original request parameters
        $query = array();
        if ($url = chronomail_utils::get_input_value('_url', chronomail_utils::INPUT_POST)) {
            parse_str($url, $query);

            // prevent endless looping on login page
            if ($query['_task'] == 'login') {
                unset($query['_task']);
            }

            // prevent redirect to compose with specified ID (#1488226)
            if ($query['_action'] == 'compose' && !empty($query['_id'])) {
                $query = array('_action' => 'compose');
            }
        }

        // allow plugins to control the redirect url after login success
        $redir = $Chronomail->plugins->exec_hook('login_after', $query + array('_task' => 'mail'));
        unset($redir['abort'], $redir['_err']);

        // send redirect
        $OUTPUT->redirect($redir, 0, true);
    }
    else {
        if (!$auth['valid']) {
            $error_code = chronomail::ERROR_INVALID_REQUEST;
        }
        else {
            $error_code = is_numeric($auth['error']) ? $auth['error'] : $Chronomail->login_error();
        }

        $error_labels = array(
            chronomail::ERROR_STORAGE          => 'storageerror',
            chronomail::ERROR_COOKIES_DISABLED => 'cookiesdisabled',
            chronomail::ERROR_INVALID_REQUEST  => 'invalidrequest',
            chronomail::ERROR_INVALID_HOST     => 'invalidhost',
            chronomail::ERROR_RATE_LIMIT       => 'accountlocked',
        );

        $error_message = !empty($auth['error']) && !is_numeric($auth['error']) ? $auth['error'] : ($error_labels[$error_code] ?: 'loginfailed');

        $OUTPUT->show_message($error_message, 'warning');

        // log failed login
        $Chronomail->log_login($auth['user'], true, $error_code);

        $Chronomail->plugins->exec_hook('login_failed', array(
            'code' => $error_code, 'host' => $auth['host'], 'user' => $auth['user']));

        $Chronomail->kill_session();
    }
}

// end session
else if ($Chronomail->task == 'logout' && isset($_SESSION['user_id'])) {
    $Chronomail->request_security_check($mode = chronomail_utils::INPUT_GET);

    $userdata = array(
        'user' => $_SESSION['username'],
        'host' => $_SESSION['storage_host'],
        'lang' => $Chronomail->user->language,
    );

    $OUTPUT->show_message('loggedout');

    $Chronomail->logout_actions();
    $Chronomail->kill_session();
    $Chronomail->plugins->exec_hook('logout_after', $userdata);
}

// check session and auth cookie
else if ($Chronomail->task != 'login' && $_SESSION['user_id']) {
    if (!$Chronomail->session->check_auth()) {
        $Chronomail->kill_session();
        $session_error = 'sessionerror';
    }
}

// not logged in -> show login page
if (empty($Chronomail->user->ID)) {
    if ($session_error || $_REQUEST['_err'] === 'session' || ($session_error = $Chronomail->session_error())) {
        $OUTPUT->show_message($session_error ?: 'sessionerror', 'error', null, true, -1);
    }

    if ($OUTPUT->ajax_call || $OUTPUT->get_env('framed')) {
        $OUTPUT->command('session_error', $Chronomail->url(array('_err' => 'session')));
        $OUTPUT->send('iframe');
    }

    // check if installer is still active
    if ($Chronomail->config->get('enable_installer') && is_readable('./installer/index.php')) {
        $OUTPUT->add_footer(html::div(array('id' => 'login-addon', 'style' => "background:#ef9398; border:2px solid #dc5757; padding:0.5em; margin:2em auto; width:50em"),
            html::tag('h2', array('style' => "margin-top:0.2em"), "Installer script is still accessible") .
            html::p(null, "The install script of your Chronomail installation is still stored in its default location!") .
            html::p(null, "Please <b>remove</b> the whole <tt>installer</tt> folder from the Chronomail directory because
                these files may expose sensitive configuration data like server passwords and encryption keys
                to the public. Make sure you cannot access the <a href=\"./installer/\">installer script</a> from your browser.")
        ));
    }

    $plugin = $Chronomail->plugins->exec_hook('unauthenticated', array(
            'task'      => 'login',
            'error'     => $session_error,
            // Return 401 only on failed logins (#7010)
            'http_code' => empty($session_error) && !empty($error_message) ? 401 : 200
    ));

    $Chronomail->set_task($plugin['task']);

    if ($plugin['http_code'] == 401) {
        header('HTTP/1.0 401 Unauthorized');
    }

    $OUTPUT->send($plugin['task']);
}
else {
    // CSRF prevention
    $Chronomail->request_security_check();

    // check access to disabled actions
    $disabled_actions = (array) $Chronomail->config->get('disabled_actions');
    if (in_array($Chronomail->task . '.' . ($Chronomail->action ?: 'index'), $disabled_actions)) {
        chronomail::raise_error(array(
            'code' => 404, 'type' => 'php',
            'message' => "Action disabled"), true, true);
    }
}

// we're ready, user is authenticated and the request is safe
$plugin = $Chronomail->plugins->exec_hook('ready', array('task' => $Chronomail->task, 'action' => $Chronomail->action));
$Chronomail->set_task($plugin['task']);
$Chronomail->action = $plugin['action'];

// handle special actions
if ($Chronomail->action == 'keep-alive') {
    $OUTPUT->reset();
    $Chronomail->plugins->exec_hook('keep_alive', array());
    $OUTPUT->send();
}
else if ($Chronomail->action == 'save-pref') {
    include INSTALL_PATH . 'program/steps/utils/save_pref.inc';
}


// include task specific functions
if (is_file($incfile = INSTALL_PATH . 'program/steps/'.$Chronomail->task.'/func.inc')) {
    include_once $incfile;
}

// allow 5 "redirects" to another action
$redirects = 0;
while ($redirects < 5) {
    // execute a plugin action
    if (preg_match('/^plugin\./', $Chronomail->action)) {
        $Chronomail->plugins->exec_action($Chronomail->action);
        break;
    }
    // execute action registered to a plugin task
    else if ($Chronomail->plugins->is_plugin_task($Chronomail->task)) {
        if (!$Chronomail->action) $Chronomail->action = 'index';
        $Chronomail->plugins->exec_action($Chronomail->task.'.'.$Chronomail->action);
        break;
    }
    // try to include the step file
    else if (($stepfile = $Chronomail->get_action_file())
        && is_file($incfile = INSTALL_PATH . 'program/steps/'.$Chronomail->task.'/'.$stepfile)
    ) {
        // include action file only once (in case it don't exit)
        include_once $incfile;
        $redirects++;
    }
    else {
        break;
    }
}

if ($Chronomail->action == 'refresh') {
    $Chronomail->plugins->exec_hook('refresh', array('last' => intval(chronomail_utils::get_input_value('_last', chronomail_utils::INPUT_GPC))));
}

// parse main template (default)
$OUTPUT->send($Chronomail->task);

// if we arrive here, something went wrong
chronomail::raise_error(array(
    'code' => 404,
    'type' => 'php',
    'line' => __LINE__,
    'file' => __FILE__,
    'message' => "Invalid request"), true, true);

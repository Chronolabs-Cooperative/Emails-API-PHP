<?php

/**
 +-----------------------------------------------------------------------+
 | This file is part of the chronomail Webmail client                     |
 |                                                                       |
 | Copyright (C) The chronomail Dev Team                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Display error message page                                          |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

$chronomail = chronomail::get_instance();

// authorization error
if ($ERROR_CODE == 401) {
    $__error_title = mb_strtoupper($chronomail->gettext('errauthorizationfailed'));
    $__error_text  = nl2br($chronomail->gettext('errunauthorizedexplain') . "\n" .
                        $chronomail->gettext('errcontactserveradmin'));
}

// forbidden due to request check
else if ($ERROR_CODE == 403) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && $chronomail->request_status == chronomail::REQUEST_ERROR_URL) {
        $url = $chronomail->url($_GET, true, false, true);
        $add = html::a($url, $chronomail->gettext('clicktoresumesession'));
    }
    else {
        $add = $chronomail->gettext('errcontactserveradmin');
    }

    $__error_title = mb_strtoupper($chronomail->gettext('errrequestcheckfailed'));
    $__error_text  = nl2br($chronomail->gettext('errcsrfprotectionexplain')) . '<p>' . $add . '</p>';
}

// failed request (wrong step in URL)
else if ($ERROR_CODE == 404) {
    $request_url   = htmlentities($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    $__error_title = mb_strtoupper($chronomail->gettext('errnotfound'));
    $__error_text  = nl2br($chronomail->gettext('errnotfoundexplain') . "\n" .
                        $chronomail->gettext('errcontactserveradmin'));

    $__error_text .= '<p><i>' . $chronomail->gettext('errfailedrequest') . ": $request_url</i></p>";
}

// browser is not compatible with this application
else if ($ERROR_CODE == 409) {
    $user_agent    = htmlentities($_SERVER['HTTP_USER_AGENT']);
    $__error_title = 'Your browser does not suit the requirements for this application';
    $__error_text  = "Required features: <i>JavaScript enabled</i> and <i>XMLHTTPRequest support</i>."
        . "<p><i>Your configuration:</i><br>$user_agent</p>";
}

// Gone, e.g. message cached but not in the storage
else if ($ERROR_CODE == 410) {
    $__error_title = 'INTERNAL ERROR';
    $__error_text  = $chronomail->gettext('messageopenerror');
}

// invalid compose ID
else if ($ERROR_CODE == 450 && $_SERVER['REQUEST_METHOD'] == 'GET' && $chronomail->action == 'compose') {
    $url = $chronomail->url('compose');

    $__error_title = mb_strtoupper($chronomail->gettext('errcomposesession'));
    $__error_text  = nl2br($chronomail->gettext('errcomposesessionexplain'))
        . '<p>' . html::a($url, $chronomail->gettext('clicktocompose')) . '</p>';
}

// database connection error
else if ($ERROR_CODE == 601) {
    $__error_title = "CONFIGURATION ERROR";
    $__error_text  =  nl2br($ERROR_MESSAGE) . "<br />Please read the INSTALL instructions!";
}

// database connection error
else if ($ERROR_CODE == 603) {
    $__error_title = "DATABASE ERROR: CONNECTION FAILED!";
    $__error_text  =  "Unable to connect to the database!<br />Please contact your server-administrator.";
}

// system error
else {
    $__error_title = "SERVICE CURRENTLY NOT AVAILABLE!";
    $__error_text  = sprintf('Error No. [%s]', $ERROR_CODE);
}

// inform plugins
if ($chronomail && $chronomail->plugins) {
    $plugin = $chronomail->plugins->exec_hook('error_page', array(
        'code'  => $ERROR_CODE,
        'title' => $__error_title,
        'text'  => $__error_text,
    ));

    if (!empty($plugin['title'])) {
        $__error_title = $plugin['title'];
    }
    if (!empty($plugin['text'])) {
        $__error_text = $plugin['text'];
    }
}

$HTTP_ERR_CODE = $ERROR_CODE && $ERROR_CODE < 600 ? $ERROR_CODE : 500;

// Ajax request
if ($chronomail->output && $chronomail->output->type == 'js') {
    header("HTTP/1.0 $HTTP_ERR_CODE $__error_title");
    die;
}

// compose page content
$__page_content = <<<EOF
<div class="boxerror">
<h3 class="error-title">$__error_title</h3>
<div class="error-text">$__error_text</div>
</div>
EOF;

if ($chronomail->output && $chronomail->output->template_exists('error')) {
    $chronomail->output->reset();
    $chronomail->output->set_env('error_task', 'error' . (empty($chronomail->user) || empty($chronomail->user->ID) ? '-login' : ''));
    $chronomail->output->set_env('server_error', $ERROR_CODE);
    $chronomail->output->set_env('comm_path', $chronomail->comm_path);
    $chronomail->output->send('error');
}

$__skin = $chronomail->config->get('skin', 'default');
$__productname = $chronomail->config->get('product_name', 'chronomail Webmail');

// print system error page
print <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<title>$__productname :: ERROR</title>
<link rel="stylesheet" type="text/css" href="skins/$__skin/common.css" />
</head>
<body>

<table border="0" cellsapcing="0" cellpadding="0" width="100%" height="80%"><tr><td align="center">

$__page_content

</td></tr></table>

</body>
</html>
EOF;

exit;

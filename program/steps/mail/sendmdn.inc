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
 |   Send a message disposition notification for a specific mail         |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

// only process ajax requests
if (!$OUTPUT->ajax_call) {
    return;
}

if (!empty($_POST['_uid'])) {
    $sent = chronomail_send_mdn(chronomail_utils::get_input_value('_uid', chronomail_utils::INPUT_POST), $smtp_error);
}

// show either confirm or error message
if ($sent) {
    $OUTPUT->set_env('mdn_request', false);
    $OUTPUT->show_message('receiptsent', 'confirmation');
}
else if ($smtp_error) {
    $OUTPUT->show_message($smtp_error['label'], 'error', $smtp_error['vars']);
}
else {
    $OUTPUT->show_message('errorsendingreceipt', 'error');
}

$OUTPUT->send();

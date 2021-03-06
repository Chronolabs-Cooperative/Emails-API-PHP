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
 |   Undelete contacts (CIDs) from last delete action                    |
 +-----------------------------------------------------------------------+
 | Author: Aleksander Machniak <machniak@kolabsys.com>                   |
 +-----------------------------------------------------------------------+
*/

// process ajax requests only
if (!$OUTPUT->ajax_call) {
    return;
}

$undo   = $_SESSION['contact_undo'];
$delcnt = 0;

foreach ((array)$undo['data'] as $source => $cid) {
    $CONTACTS = chronomail_contact_source($source);

    $plugin = $RCMAIL->plugins->exec_hook('contact_undelete', array(
        'id' => $cid, 'source' => $source));

    $restored = !$plugin['abort'] ? $CONTACTS->undelete($cid) : $plugin['result'];

    if (!$restored) {
        $OUTPUT->show_message($plugin['message'] ?: 'contactrestoreerror', 'error');
        $OUTPUT->command('list_contacts');
        $OUTPUT->send();
    }
    else {
        $delcnt += $restored;
    }
}

$RCMAIL->session->remove('contact_undo');

$OUTPUT->show_message('contactrestored', 'confirmation');
$OUTPUT->command('list_contacts');

// send response
$OUTPUT->send();

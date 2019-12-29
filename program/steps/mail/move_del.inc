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
 |   Move the submitted messages to a specific mailbox or delete them    |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

// only process ajax requests
if (!$OUTPUT->ajax_call) {
    return;
}

// count messages before changing anything
$threading = (bool) $RCMAIL->storage->get_threading();
$trash     = $RCMAIL->config->get('trash_mbox');
$sources   = array();

if ($_POST['_from'] != 'show') {
    $old_count = $RCMAIL->storage->count(NULL, $threading ? 'THREADS' : 'ALL');
}

// move messages
if ($RCMAIL->action == 'move' && !empty($_POST['_uid']) && strlen($_POST['_target_mbox'])) {
    $target  = chronomail_utils::get_input_value('_target_mbox', chronomail_utils::INPUT_POST, true);
    $success = true;

    foreach (chronomail::get_uids(null, null, $multifolder, chronomail_utils::INPUT_POST) as $mbox => $uids) {
        if ($mbox === $target) {
            $count += is_array($uids) ? count($uids) : 1;
        }
        else if ($RCMAIL->storage->move_message($uids, $target, $mbox)) {
            $count += is_array($uids) ? count($uids) : 1;
            $sources[] = $mbox;
        }
        else {
            $success = false;
        }
    }

    if (!$success) {
        // send error message
        if ($_POST['_from'] != 'show') {
            $OUTPUT->command('list_mailbox');
        }

        $RCMAIL->display_server_error('errormoving', null, $target == $trash ? 'delete' : '');
        $OUTPUT->send();
    }
    else {
        $OUTPUT->show_message($target == $trash ? 'messagemovedtotrash' : 'messagemoved', 'confirmation');
    }

    if (!empty($_POST['_refresh'])) {
        // FIXME: send updated message rows instead of reloading the entire list
        $OUTPUT->command('refresh_list');
    }
    else {
        $addrows = true;
    }
}
// delete messages
else if ($RCMAIL->action == 'delete' && !empty($_POST['_uid'])) {
    foreach (chronomail::get_uids(null, null, $multifolder, chronomail_utils::INPUT_POST) as $mbox => $uids) {
        $del      += (int)$RCMAIL->storage->delete_message($uids, $mbox);
        $count    += is_array($uids) ? count($uids) : 1;
        $sources[] = $mbox;
    }

    if (!$del) {
        // send error message
        if ($_POST['_from'] != 'show') {
            $OUTPUT->command('list_mailbox');
        }

        $RCMAIL->display_server_error('errordeleting');
        $OUTPUT->send();
    }
    else {
        $OUTPUT->show_message('messagedeleted', 'confirmation');
    }

    $addrows = true;
}
// unknown action or missing query param
else {
    $OUTPUT->show_message('internalerror', 'error');
    $OUTPUT->send();
}

$search_request = chronomail_utils::get_input_value('_search', chronomail_utils::INPUT_GPC);

// refresh saved search set after moving some messages
if ($search_request && $RCMAIL->storage->get_search_set()) {
    $_SESSION['search'] = $RCMAIL->storage->refresh_search();
}

if ($_POST['_from'] == 'show') {
    if ($next = chronomail_utils::get_input_value('_next_uid', chronomail_utils::INPUT_GPC)) {
        $OUTPUT->command('show_message', $next);
    }
    else {
        $OUTPUT->command('command', 'list');
    }

    $OUTPUT->send();
}

$mbox           = $RCMAIL->storage->get_folder();
$msg_count      = $RCMAIL->storage->count(NULL, $threading ? 'THREADS' : 'ALL');
$exists         = $RCMAIL->storage->count($mbox, 'EXISTS', true);
$page_size      = $RCMAIL->storage->get_pagesize();
$page           = $RCMAIL->storage->get_page();
$pages          = ceil($msg_count / $page_size);
$nextpage_count = $old_count - $page_size * $page;
$remaining      = $msg_count - $page_size * ($page - 1);

// jump back one page (user removed the whole last page)
if ($page > 1 && $remaining == 0) {
    $page -= 1;
    $RCMAIL->storage->set_page($page);
    $_SESSION['page'] = $page;
    $jump_back = true;
}

// update unseen messages counts for all involved folders
foreach ($sources as $source) {
    chronomail_send_unread_count($source, true);
}

if ($RCMAIL->action == 'move' && strlen($target)) {
    chronomail_send_unread_count($target, true);
}

// update message count display
$OUTPUT->set_env('messagecount', $msg_count);
$OUTPUT->set_env('current_page', $page);
$OUTPUT->set_env('pagecount', $pages);
$OUTPUT->set_env('exists', $exists);
$OUTPUT->command('set_quota', $RCMAIL->quota_content(null, $multifolder ? $sources[0] : 'INBOX'));
$OUTPUT->command('set_rowcount', chronomail_get_messagecount_text($msg_count), $mbox);

if ($threading) {
    $count = chronomail_utils::get_input_value('_count', chronomail_utils::INPUT_POST);
}

// add new rows from next page (if any)
if ($addrows && $count && $uids != '*' && ($jump_back || $nextpage_count > 0)) {
    // #5862: Don't add more rows than it was on the next page
    $count = $jump_back ? null : min($nextpage_count, $count);

    $a_headers = $RCMAIL->storage->list_messages($mbox, NULL,
        chronomail_sort_column(), chronomail_sort_order(), $count);

    chronomail_js_message_list($a_headers, false);
}

// set trash folder state
if ($mbox === $trash) {
    $OUTPUT->command('set_trash_count', $exists);
}
else if ($target !== null && $target === $trash) {
    $OUTPUT->command('set_trash_count', $RCMAIL->storage->count($trash, 'EXISTS', true));
}

// send response
$OUTPUT->send();

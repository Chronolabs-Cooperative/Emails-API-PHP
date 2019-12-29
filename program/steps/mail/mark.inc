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
 |   Mark the submitted messages with the specified flag                 |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

// only process ajax requests
if (!$OUTPUT->ajax_call) {
    return;
}

$threading    = (bool) $RCMAIL->storage->get_threading();
$skip_deleted = (bool) $RCMAIL->config->get('skip_deleted');
$read_deleted = (bool) $RCMAIL->config->get('read_when_deleted');

$a_flags_map  = array(
    'undelete'  => 'UNDELETED',
    'delete'    => 'DELETED',
    'read'      => 'SEEN',
    'unread'    => 'UNSEEN',
    'flagged'   => 'FLAGGED',
    'unflagged' => 'UNFLAGGED',
);

$_uids   = chronomail_utils::get_input_value('_uid', chronomail_utils::INPUT_POST);
$flag    = chronomail_utils::get_input_value('_flag', chronomail_utils::INPUT_POST);
$folders = chronomail_utils::get_input_value('_folders', chronomail_utils::INPUT_POST);
$mbox    = chronomail_utils::get_input_value('_mbox', chronomail_utils::INPUT_POST);

if ($_uids && $flag) {
    $flag = $a_flags_map[$flag] ?: strtoupper($flag);

    if ($flag == 'DELETED' && $skip_deleted && $_POST['_from'] != 'show') {
        // count messages before changing anything
        $old_count = $RCMAIL->storage->count(NULL, $threading ? 'THREADS' : 'ALL');
        $old_pages = ceil($old_count / $RCMAIL->storage->get_pagesize());
    }

    if ($folders == 'all') {
        $mboxes = $RCMAIL->storage->list_folders_subscribed('', '*', 'mail');
        $input  = array_combine($mboxes, array_fill(0, count($mboxes), '*'));
    }
    else if ($folders == 'sub') {
        $delim  = $RCMAIL->storage->get_hierarchy_delimiter();
        $mboxes = $RCMAIL->storage->list_folders_subscribed($mbox . $delim, '*', 'mail');
        array_unshift($mboxes, $mbox);
        $input = array_combine($mboxes, array_fill(0, count($mboxes), '*'));
    }
    else if ($folders == 'cur') {
        $input = array($mbox => '*');
    }
    else {
        $input = chronomail::get_uids(null, null, $dummy, chronomail_utils::INPUT_POST);
    }

    foreach ($input as $mbox => $uids) {
        $marked += (int)$RCMAIL->storage->set_flag($uids, $flag, $mbox);
        $count  += is_array($uids) ? count($uids) : 1;
    }

    if (!$marked) {
        // send error message
        if ($_POST['_from'] != 'show') {
            $OUTPUT->command('list_mailbox');
        }

        $RCMAIL->display_server_error('errormarking');
        $OUTPUT->send();
        exit;
    }
    else if (empty($_POST['_quiet'])) {
        $OUTPUT->show_message('messagemarked', 'confirmation');
    }

    if ($flag == 'DELETED' && $read_deleted && !empty($_POST['_ruid'])) {
        if ($ruids = chronomail_utils::get_input_value('_ruid', chronomail_utils::INPUT_POST)) {
            foreach (chronomail::get_uids($ruids) as $mbox => $uids) {
                $read += (int)$RCMAIL->storage->set_flag($uids, 'SEEN', $mbox);
            }
        }

        if ($read && !$skip_deleted) {
            $OUTPUT->command('flag_deleted_as_read', $ruids);
        }
    }

    if ($flag == 'SEEN' || $flag == 'UNSEEN' || ($flag == 'DELETED' && !$skip_deleted)) {
        foreach ($input as $mbox => $uids) {
            chronomail_send_unread_count($mbox);
        }

        $OUTPUT->set_env('last_flag', $flag);
    }
    else if ($flag == 'DELETED' && $skip_deleted) {
        if ($_POST['_from'] == 'show') {
            if ($next = chronomail_utils::get_input_value('_next_uid', chronomail_utils::INPUT_GPC))
                $OUTPUT->command('show_message', $next);
            else
                $OUTPUT->command('command', 'list');
        }
        else {
            $search_request = chronomail_utils::get_input_value('_search', chronomail_utils::INPUT_GPC);

            // refresh saved search set after moving some messages
            if ($search_request && $RCMAIL->storage->get_search_set()) {
                $_SESSION['search'] = $RCMAIL->storage->refresh_search();
            }

            $msg_count      = $RCMAIL->storage->count(NULL, $threading ? 'THREADS' : 'ALL');
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

            foreach ($input as $mbox => $uids) {
                chronomail_send_unread_count($mbox, true);
            }

            // update message count display
            $OUTPUT->set_env('messagecount', $msg_count);
            $OUTPUT->set_env('current_page', $page);
            $OUTPUT->set_env('pagecount', $pages);
            $OUTPUT->command('set_rowcount', chronomail_get_messagecount_text($msg_count), $mbox);

            if ($threading) {
                $count = chronomail_utils::get_input_value('_count', chronomail_utils::INPUT_POST);
            }

            // add new rows from next page (if any)
            if ($old_count && $_uids != '*' && ($jump_back || $nextpage_count > 0)) {
                // #5862: Don't add more rows than it was on the next page
                $count = $jump_back ? null : min($nextpage_count, $count);

                $a_headers = $RCMAIL->storage->list_messages($mbox, null,
                    chronomail_sort_column(), chronomail_sort_order(), $count);

                chronomail_js_message_list($a_headers, false);
            }
        }
    }
}
else {
    $OUTPUT->show_message('internalerror', 'error');
}

$OUTPUT->send();

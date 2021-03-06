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
 |   Mail messages search action                                         |
 +-----------------------------------------------------------------------+
 | Author: Benjamin Smith <defitro@gmail.com>                            |
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

$REMOTE_REQUEST = TRUE;

@set_time_limit(170);  // extend default max_execution_time to ~3 minutes

// reset list_page and old search results
$RCMAIL->storage->set_page(1);
$RCMAIL->storage->set_search_set(NULL);
$_SESSION['page'] = 1;

// using encodeURI with javascript "should" give us
// a correctly encoded query string
$imap_charset = RCUBE_CHARSET;

// get search string
$str     = chronomail_utils::get_input_value('_q', chronomail_utils::INPUT_GET, true);
$mbox    = chronomail_utils::get_input_value('_mbox', chronomail_utils::INPUT_GET, true);
$filter  = chronomail_utils::get_input_value('_filter', chronomail_utils::INPUT_GET);
$headers = chronomail_utils::get_input_value('_headers', chronomail_utils::INPUT_GET);
$scope   = chronomail_utils::get_input_value('_scope', chronomail_utils::INPUT_GET);
$interval = chronomail_utils::get_input_value('_interval', chronomail_utils::INPUT_GET);
$continue = chronomail_utils::get_input_value('_continue', chronomail_utils::INPUT_GET);
$subject = array();

$filter         = trim($filter);
$search_request = md5($mbox.$scope.$interval.$filter.$str);

// add list filter string
$search_str = $filter && $filter != 'ALL' ? $filter : '';

// Check the search string for type of search
if (preg_match("/^from:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['from'] = "HEADER FROM";
}
else if (preg_match("/^to:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['to'] = "HEADER TO";
}
else if (preg_match("/^cc:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['cc'] = "HEADER CC";
}
else if (preg_match("/^bcc:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['bcc'] = "HEADER BCC";
}
else if (preg_match("/^subject:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['subject'] = "HEADER SUBJECT";
}
else if (preg_match("/^body:.*/i", $str)) {
    list(,$srch) = explode(":", $str);
    $subject['body'] = "BODY";
}
else if (strlen(trim($str))) {
    if ($headers) {
        foreach (explode(',', $headers) as $header) {
            if ($header == 'text') {
                // #1488208: get rid of other headers when searching by "TEXT"
                $subject = array('text' => 'TEXT');
                break;
            }
            else {
                $subject[$header] = ($header != 'body' ? 'HEADER ' : '') . strtoupper($header);
            }
        }

        // save search modifiers for the current folder to user prefs
        $mkey               = $scope == 'all' ? '*' : $mbox;
        $search_mods        = chronomail_search_mods();
        $search_mods[$mkey] = array_fill_keys(array_keys($subject), 1);

        $RCMAIL->user->save_prefs(array('search_mods' => $search_mods));
    }
    else {
        // search in subject by default
        $subject['subject'] = 'HEADER SUBJECT';
    }
}

$search = isset($srch) ? trim($srch) : trim($str);

if ($search_interval = chronomail_search_interval_criteria($interval)) {
    $search_str .= ' ' . $search_interval;
}

if (!empty($subject)) {
    $search_str .= str_repeat(' OR', count($subject)-1);
    foreach ($subject as $sub) {
        $search_str .= ' ' . $sub . ' ' . chronomail_imap_generic::escape($search);
    }
}

$search_str  = trim($search_str);
$sort_column = chronomail_sort_column();

// set message set for already stored (but incomplete) search request
if (!empty($continue) && isset($_SESSION['search']) && $_SESSION['search_request'] == $continue) {
    $RCMAIL->storage->set_search_set($_SESSION['search']);
    $search_str = $_SESSION['search'][0];
}

// execute IMAP search
if ($search_str) {
    // search all, current or subfolders folders
    if ($scope == 'all') {
        $mboxes = $RCMAIL->storage->list_folders_subscribed('', '*', 'mail', null, true);
        natcasesort($mboxes);  // we want natural alphabetic sorting of folders in the result set
    }
    else if ($scope == 'sub') {
        $delim  = $RCMAIL->storage->get_hierarchy_delimiter();
        $mboxes = $RCMAIL->storage->list_folders_subscribed($mbox . $delim, '*', 'mail');
        array_unshift($mboxes, $mbox);
    }

    if ($scope != 'all') {
        // Remember current folder, it can change in meantime (plugins)
        // but we need it to e.g. recognize Sent folder to handle From/To column later
        $RCMAIL->output->set_env('mailbox', $mbox);
    }

    $result = $RCMAIL->storage->search($mboxes, $search_str, $imap_charset, $sort_column);
}

// save search results in session
if (!is_array($_SESSION['search'])) {
    $_SESSION['search'] = array();
}

if ($search_str) {
    $_SESSION['search'] = $RCMAIL->storage->get_search_set();
    $_SESSION['last_text_search'] = $str;
}
$_SESSION['search_request']  = $search_request;
$_SESSION['search_scope']    = $scope;
$_SESSION['search_interval'] = $interval;
$_SESSION['search_filter']   = $filter;

// Get the headers
if (!$result->incomplete) {
    $result_h = $RCMAIL->storage->list_messages($mbox, 1, $sort_column, chronomail_sort_order());
}

// Make sure we got the headers
if (!empty($result_h)) {
    $count = $RCMAIL->storage->count($mbox, $RCMAIL->storage->get_threading() ? 'THREADS' : 'ALL');

    chronomail_js_message_list($result_h, false);

    if ($search_str) {
        $OUTPUT->show_message('searchsuccessful', 'confirmation', array('nr' => $RCMAIL->storage->count(NULL, 'ALL')));
    }

    // remember last HIGHESTMODSEQ value (if supported)
    // we need it for flag updates in check-recent
    if ($mbox !== null) {
        $data = $RCMAIL->storage->folder_data($mbox);
        if (!empty($data['HIGHESTMODSEQ'])) {
            $_SESSION['list_mod_seq'] = $data['HIGHESTMODSEQ'];
        }
    }
}
// handle IMAP errors (e.g. #1486905)
else if ($err_code = $RCMAIL->storage->get_error_code()) {
    $count = 0;
    $RCMAIL->display_server_error();
}
// advice the client to re-send the (cross-folder) search request
else if ($result->incomplete) {
    $count = 0;  // keep UI locked
    $OUTPUT->command('continue_search', $search_request);
}
else {
    $count = 0;
    $OUTPUT->show_message('searchnomatch', 'notice');
    $OUTPUT->set_env('multifolder_listing', (bool)$result->multi);
    if ($result->multi && $scope == 'all') {
        $OUTPUT->command('select_folder', '');
    }
}

// update message count display
$OUTPUT->set_env('search_request', $search_str ? $search_request : '');
$OUTPUT->set_env('search_filter', $_SESSION['search_filter']);
$OUTPUT->set_env('messagecount', $count);
$OUTPUT->set_env('pagecount', ceil($count/$RCMAIL->storage->get_pagesize()));
$OUTPUT->set_env('exists', $mbox === null ? 0 : $RCMAIL->storage->count($mbox, 'EXISTS'));
$OUTPUT->command('set_rowcount', chronomail_get_messagecount_text($count, 1), $mbox);

chronomail_list_pagetitle();

// update unseen messages count
if (empty($search_str)) {
    chronomail_send_unread_count($mbox, false, empty($result_h) ? 0 : null);
}

if (!$result->incomplete) {
    $OUTPUT->command('set_quota', $RCMAIL->quota_content(null, $result->multi ? 'INBOX' : $mbox));
}

$OUTPUT->send();


// Creates BEFORE/SINCE search criteria from the specified interval
// Interval can be: 1W, 1M, 1Y, -1W, -1M, -1Y
function chronomail_search_interval_criteria($interval)
{
    if (empty($interval)) {
        return;
    }

    if ($interval[0] == '-') {
        $search   = 'BEFORE';
        $interval = substr($interval, 1);
    }
    else {
        $search = 'SINCE';
    }

    $date     = new DateTime('now');
    $interval = new DateInterval('P' . $interval);

    $date->sub($interval);

    return $search . ' ' . $date->format('j-M-Y');
}

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
 |   Search contacts from the address book widget                        |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

$search        = chronomail_utils::get_input_value('_q', chronomail_utils::INPUT_GPC, true);
$sources       = $RCMAIL->get_address_sources();
$search_mode   = (int) $RCMAIL->config->get('addressbook_search_mode');
$addr_sort_col = $RCMAIL->config->get('addressbook_sort_col', 'name');
$afields       = $RCMAIL->config->get('contactlist_fields');
$page_size     = $RCMAIL->config->get('addressbook_pagesize', $RCMAIL->config->get('pagesize', 50));
$records       = array();
$search_set    = array();
$jsresult      = array();
$search_mode  |= chronomail_addressbook::SEARCH_GROUPS;

foreach ($sources as $s) {
    $source = $RCMAIL->get_address_book($s['id']);
    $source->set_page(1);
    $source->set_pagesize(9999);

    // list matching groups of this source
    if ($source->groups) {
        $jsresult += chronomail_compose_contact_groups($source, $s['id'], $search, $search_mode);
    }

    // get contacts count
    $result = $source->search($afields, $search, $search_mode, true, true, 'email');

    if (!$result->count) {
        continue;
    }

    while ($row = $result->next()) {
        $row['sourceid'] = $s['id'];
        $key = chronomail_addressbook::compose_contact_key($row, $addr_sort_col);
        $records[$key] = $row;
    }

    $search_set[$s['id']] = $source->get_search_set();
    unset($result);
}

$group_count = count($jsresult);

// sort the records
ksort($records, SORT_LOCALE_STRING);

// create resultset object
$count  = count($records);
$result = new chronomail_result_set($count);

// select the requested page
if ($page_size < $count) {
    $records = array_slice($records, $result->first, $page_size);
}

$result->records = array_values($records);

if (!empty($result) && $result->count > 0) {
    // create javascript list
    while ($row = $result->next()) {
        $name      = chronomail_addressbook::compose_list_name($row);
        $classname = $row['_type'] == 'group' ? 'group' : 'person';
        $keyname   = $row['_type'] == 'group' ? 'contactgroup' : 'contact';

        // add record for every email address of the contact
        // (same as in list_contacts.inc)
        $emails = $source->get_col_values('email', $row, true);
        foreach ($emails as $i => $email) {
            $row_id = $row['sourceid'].'-'.$row['ID'].'-'.$i;
            $jsresult[$row_id] = format_email_recipient($email, $name);
            $title = chronomail_addressbook::compose_search_name($row, $email, $name);

            $OUTPUT->command('add_contact_row', $row_id, array(
                $keyname => html::a(array('title' => $title), chronomail::Q($name ?: $email) .
                    ($name && count($emails) > 1 ? '&nbsp;' . html::span('email', chronomail::Q($email)) : '')
                )), $classname);
        }
    }

    // search request ID
    $search_request = md5('composeaddr' . $search);

    // save search settings in session
    $_SESSION['search'][$search_request] = $search_set;
    $_SESSION['search_params'] = array('id' => $search_request, 'data' => array($afields, $search));

    $OUTPUT->show_message('contactsearchsuccessful', 'confirmation', array('nr' => $result->count));

    $OUTPUT->set_env('search_request', $search_request);
    $OUTPUT->set_env('source', '');
    $OUTPUT->command('unselect_directory');
}
else if (!$group_count) {
    $OUTPUT->show_message('nocontactsfound', 'notice');
}

// update env
$OUTPUT->set_env('contactdata', $jsresult);
$OUTPUT->set_env('pagecount', ceil($result->count / $page_size));
$OUTPUT->command('set_page_buttons');

// send response
$OUTPUT->send();

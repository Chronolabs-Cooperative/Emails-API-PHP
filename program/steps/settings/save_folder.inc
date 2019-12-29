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
 |   Provide functionality to create/edit a folder                       |
 +-----------------------------------------------------------------------+
 | Author: Aleksander Machniak <alec@alec.pl>                            |
 +-----------------------------------------------------------------------+
*/

// WARNING: folder names in UI are encoded with RCUBE_CHARSET

// init IMAP connection
$STORAGE = $RCMAIL->get_storage();

$name      = trim(chronomail_utils::get_input_value('_name', chronomail_utils::INPUT_POST, true));
$path      = chronomail_utils::get_input_value('_parent', chronomail_utils::INPUT_POST, true);
$old_imap  = chronomail_utils::get_input_value('_mbox', chronomail_utils::INPUT_POST, true);
$type      = chronomail_utils::get_input_value('_type', chronomail_utils::INPUT_POST);
$name_imap = chronomail_charset::convert($name, RCUBE_CHARSET, 'UTF7-IMAP');
// $path is in UTF7-IMAP already

$delimiter = $STORAGE->get_hierarchy_delimiter();
$options = strlen($old_imap) ? chronomail_folder_options($old_imap) : array();

// Folder name checks
if ($options['protected'] || $options['norename']) {
}
else if (!strlen($name)) {
    $error = $RCMAIL->gettext('namecannotbeempty');
}
else if (mb_strlen($name) > 128) {
    $error = $RCMAIL->gettext('nametoolong');
}
else if ($name[0] == '.' && $RCMAIL->config->get('imap_skip_hidden_folders')) {
    $error = $RCMAIL->gettext('namedotforbidden');
}
else if (!$STORAGE->folder_validate($name, $char)) {
    $error = $RCMAIL->gettext('forbiddencharacter') . " ($char)";
}

if ($error) {
    $OUTPUT->command('display_message', $error, 'error');
}
else {
    if ($options['protected'] || $options['norename']) {
        $name_imap = $old_imap;
    }
    else if (strlen($path)) {
        $name_imap = $path . $delimiter . $name_imap;
    }
    else {
        $name_imap = $STORAGE->mod_folder($name_imap, 'in');
    }
}

$dual_use_supported = $STORAGE->get_capability(chronomail_storage::DUAL_USE_FOLDERS);
$acl_supported      = $STORAGE->get_capability('ACL');

// Check access rights to the parent folder
if (!$error && $acl_supported && strlen($path) && (!strlen($old_imap) || $old_imap != $name_imap)) {
    $parent_opts = $STORAGE->folder_info($path);
    if ($parent_opts['namespace'] != 'personal'
        && (empty($parent_opts['rights']) || !preg_match('/[ck]/', implode($parent_opts['rights'])))
    ) {
        $error = $RCMAIL->gettext('parentnotwritable');
    }
}

if ($error) {
    $OUTPUT->command('display_message', $error, 'error');
}
else {
    $folder['name']     = $name_imap;
    $folder['oldname']  = $old_imap;
    $folder['class']    = '';
    $folder['options']  = $options;
    $folder['settings'] = array(
        // List view mode: 0-list, 1-threads
        'view_mode'   => (int) chronomail_utils::get_input_value('_viewmode', chronomail_utils::INPUT_POST),
        'sort_column' => chronomail_utils::get_input_value('_sortcol', chronomail_utils::INPUT_POST),
        'sort_order'  => chronomail_utils::get_input_value('_sortord', chronomail_utils::INPUT_POST),
    );
}

// create a new mailbox
if (!$error && !strlen($old_imap)) {
    $folder['subscribe'] = true;

    // Server does not support both sub-folders and messages in a folder
    // For folders that are supposed to contain other folders we will:
    //    - disable subscribtion
    //    - add a separator at the end to make them \NoSelect
    if (!$dual_use_supported && $type == 'folder') {
        $folder['subscribe'] = false;
        $folder['noselect']  = true;
    }

    $plugin = $RCMAIL->plugins->exec_hook('folder_create', array('record' => $folder));

    $folder = $plugin['record'];

    if (!$plugin['abort']) {
        $created = $STORAGE->create_folder($folder['name'], $folder['subscribe'], null, $folder['noselect']);
    }
    else {
        $created = $plugin['result'];
    }

    if ($created) {
        // Save folder settings
        if (isset($_POST['_viewmode'])) {
            $a_threaded = (array) $RCMAIL->config->get('message_threading', array());

            $a_threaded[$folder['name']] = (bool) $_POST['_viewmode'];

            $RCMAIL->user->save_prefs(array('message_threading' => $a_threaded));
        }

        chronomail_update_folder_row($folder['name'], null, $folder['subscribe'], $folder['class']);

        $OUTPUT->show_message('foldercreated', 'confirmation');
        // reset folder preview frame
        $OUTPUT->command('subscription_select');
        $OUTPUT->send('iframe');
    }
    else {
        // show error message
        $OUTPUT->show_message($plugin['message'] ?: 'errorsaving', 'error', null, false);
    }
}
// update a mailbox
else if (!$error) {
    $plugin = $RCMAIL->plugins->exec_hook('folder_update', array('record' => $folder));

    $folder = $plugin['record'];
    $rename = ($folder['oldname'] != $folder['name']);

    if (!$plugin['abort']) {
        if ($rename) {
            $updated = $STORAGE->rename_folder($folder['oldname'], $folder['name']);
        }
        else {
            $updated = true;
        }
    }
    else {
        $updated = $plugin['result'];
    }

    if ($updated) {
        // Update folder settings,
        if (isset($_POST['_viewmode'])) {
            $a_threaded = (array) $RCMAIL->config->get('message_threading', array());

            // In case of name change update names of childrens in settings
            if ($rename) {
                $oldprefix  = '/^' . preg_quote($folder['oldname'] . $delimiter, '/') . '/';
                foreach ($a_threaded as $key => $val) {
                    if ($key == $folder['oldname']) {
                        unset($a_threaded[$key]);
                    }
                    else if (preg_match($oldprefix, $key)) {
                        unset($a_threaded[$key]);
                        $a_threaded[preg_replace($oldprefix, $folder['name'].$delimiter, $key)] = $val;
                    }
                }
            }

            $a_threaded[$folder['name']] = (bool) $_POST['_viewmode'];

            $RCMAIL->user->save_prefs(array('message_threading' => $a_threaded));
        }

        $OUTPUT->show_message('folderupdated', 'confirmation');
        $OUTPUT->set_env('folder', $folder['name']);

        if ($rename) {
            // #1488692: update session
            if ($_SESSION['mbox'] === $folder['oldname']) {
                $_SESSION['mbox'] = $folder['name'];
            }
            chronomail_update_folder_row($folder['name'], $folder['oldname'], $folder['subscribe'], $folder['class']);
            $OUTPUT->send('iframe');
        }
        else if (!empty($folder['class'])) {
            chronomail_update_folder_row($folder['name'], $folder['oldname'], $folder['subscribe'], $folder['class']);
        }
    }
    else {
        // show error message
        $OUTPUT->show_message($plugin['message'] ?: 'errorsaving', 'error', null, false);
    }
}

$RCMAIL->overwrite_action('edit-folder');

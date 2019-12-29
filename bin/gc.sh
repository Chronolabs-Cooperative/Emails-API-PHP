#!/usr/bin/env php
<?php
/*
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
 |   Trigger garbage collecting routines manually (e.g. via cronjob)     |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

define('INSTALL_PATH', realpath(__DIR__ . '/..') . '/' );

require INSTALL_PATH.'program/include/clisetup.php';

$chronomail = chronomail::get_instance();

$session_driver   = $chronomail->config->get('session_storage', 'db');
$session_lifetime = $chronomail->config->get('session_lifetime', 0) * 60 * 2;

// Clean expired SQL sessions
if ($session_driver == 'db' && $session_lifetime) {
    $db = $chronomail->get_dbh();
    $db->query("DELETE FROM " . $db->table_name('session')
        . " WHERE changed < " . $db->now(-$session_lifetime));
}

// Clean caches and temp directory
$chronomail->gc();

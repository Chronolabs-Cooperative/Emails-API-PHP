#!/usr/bin/env php
<?php
/*
 +-----------------------------------------------------------------------+
 | This file is part of the chronomail Webmail client                     |
 |                                                                       |
 | Copyright (C) The chronomail Dev Team                                  |
 | Copyright (C) Kolab Systems AG                                        |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Update database schema                                              |
 +-----------------------------------------------------------------------+
 | Author: Aleksander Machniak <alec@alec.pl>                            |
 +-----------------------------------------------------------------------+
*/

define('INSTALL_PATH', realpath(__DIR__ . '/..') . '/' );

require_once INSTALL_PATH . 'program/include/clisetup.php';

// get arguments
$opts = chronomail_utils::get_opt(array(
    'v' => 'version',
    'd' => 'dir',
    'p' => 'package',
));

if (empty($opts['dir'])) {
    chronomail::raise_error("Database schema directory not specified (--dir).", false, true);
}
if (empty($opts['package'])) {
    chronomail::raise_error("Database schema package name not specified (--package).", false, true);
}

chronomail_utils::db_update($opts['dir'], $opts['package'], $opts['version'], array('errors' => true));

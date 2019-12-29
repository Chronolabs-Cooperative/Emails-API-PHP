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
 |   Setup the command line environment and provide some utitlity        |
 |   functions.                                                          |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

if (php_sapi_name() != 'cli') {
    die('Not on the "shell" (php-cli).');
}

require_once INSTALL_PATH . 'program/include/iniset.php';

// Unset max. execution time limit, set to 120 seconds in iniset.php
@set_time_limit(0);

$chronomail = chronomail::get_instance();
$chronomail->output = new chronomail_output_cli();

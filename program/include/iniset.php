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
 |   Setup the application environment required to process               |
 |   any request.                                                        |
 +-----------------------------------------------------------------------+
 | Author: Till Klampaeckel <till@php.net>                               |
 |         Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

// application constants
define('RCMAIL_VERSION', '1.4.1');
define('RCMAIL_START', microtime(true));

if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
}

if (!defined('RCMAIL_CONFIG_DIR')) {
    define('RCMAIL_CONFIG_DIR', getenv('ROUNDCUBE_CONFIG_DIR') ?: (INSTALL_PATH . 'include'));
}

if (!defined('RCUBE_LOCALIZATION_DIR')) {
    define('RCUBE_LOCALIZATION_DIR', INSTALL_PATH . 'program/localization/');
}

define('RCUBE_INSTALL_PATH', INSTALL_PATH);
define('RCUBE_CONFIG_DIR',  RCMAIL_CONFIG_DIR.'/');


// RC include folders MUST be included FIRST to avoid other
// possible not compatible libraries (i.e PEAR) to be included
// instead the ones provided by RC
$include_path = INSTALL_PATH . 'program/lib' . PATH_SEPARATOR;
$include_path.= ini_get('include_path');

if (set_include_path($include_path) === false) {
    die("Fatal error: ini_set/set_include_path does not work.");
}

// increase maximum execution time for php scripts
// (does not work in safe mode)
@set_time_limit(120);

// include composer autoloader (if available)
if (@file_exists(INSTALL_PATH . 'vendor/autoload.php')) {
    require INSTALL_PATH . 'vendor/autoload.php';
}

// include chronomail Framework
require_once 'chronomail/bootstrap.php';

// register autoloader for chronomail app classes
spl_autoload_register('chronomail_autoload');

/**
 * PHP5 autoloader routine for dynamic class loading
 */
function chronomail_autoload($classname)
{
    if (strpos($classname, 'chronomail') === 0) {
        $filepath = INSTALL_PATH . "program/include/$classname.php";
        if (is_readable($filepath)) {
            include_once $filepath;
            return true;
        }
    }

    return false;
}

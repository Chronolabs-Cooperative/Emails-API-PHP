<?php
/**
 * Email Account Propogation REST Services API
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://syd.au.snails.email
 * @license         ACADEMIC APL 2 (https://sourceforge.net/u/chronolabscoop/wiki/Academic%20Public%20License%2C%20version%202.0/)
 * @license         GNU GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @package         emails-api
 * @since           1.1.11
 * @author          Dr. Simon Antony Roberts <simon@snails.email>
 * @version         1.1.11
 * @description		A REST API for the creation and management of emails/forwarders and domain name parks for email
 * @link            http://internetfounder.wordpress.com
 * @link            https://github.com/Chronolabs-Cooperative/Emails-API-PHP
 * @link            https://sourceforge.net/p/chronolabs-cooperative
 * @link            https://facebook.com/ChronolabsCoop
 * @link            https://twitter.com/ChronolabsCoop
 * 
 */


if (!defined('API_INSTALL')) {
    die('API Custom Installation die');
}

$configs = array();

// setup config site info
$configs['db_types'] = array('mysql' => 'mysqli');

// setup config site info
$configs['conf_names'] = array(
);

// languages config files
$configs['language_files'] = array(
    'global');

// extension_loaded
$configs['extensions'] = array(
);

// Writable files and directories
$configs['writable'] = array(
    'uploads/',
    'data/',
    'include/',
    'mainfile.php',
    'include/license.php',
    'include/dbconfig.php',
    );


// Modules to be installed by default
$configs['modules'] = array();

// api_lib, api_tmp directories
$configs['apiPathDefault'] = array(
    'lib'  => 'data');

// writable api_lib, api_tmp directories
$configs['tmpPath'] = array(
    'caches'  => __DIR__ . '/caches',
    'includes' => __DIR__ . '/include',
    'tmp'    => '/tmp');

// GeoIP Resource data files default paths
$configs['api_url'] = array(
    'strata' => 'http://strata.snails.email',
    'whois' => 'http://whois.snails.email',
    'masterhost' => $_SESSION['settings']['URL'],
);

// GeoIP Resource data files default paths
$configs['service_hostname'] = array(
    'imap' => 'imap.'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
    'pop3' => 'pop3.'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
    'smtp' => 'smtp.'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
    'web' => 'webmail.'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
);

$configs['api_user'] = array(
    'zones' => '',
);

$configs['api_pass'] = array(
    'zones' => '',
);

$configs['api_urls'] = array(
    'zones' => 'http://zones.snails.email',
);

$configs['path'] = array(
    'maildir' => '/mailboxs',
    'homedir' => '/var/www/homes',
);


$configs['pgp_keys'] = array(
    'email' => 'pgpkeys@'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
    'imap' => 'imap.'.parse_url($_SESSION['settings']['URL'], PHP_URL_HOST),
    'port' => '993',
    'user' => '',
    'pass' => '',
    'maxbits' => mt_rand(2048, 4096),
    'minbits' => mt_rand(1024, 2047),
);

$configs['inbox_sizes'] = array(
    'maximum' => 1024,
    'minimum' => 20,
    'intialise' => 20,
    'offline' => 5,
    'depreciated' => 0,
);

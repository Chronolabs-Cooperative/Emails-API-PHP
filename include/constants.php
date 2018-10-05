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



// APIs URLS
define('API_STRATA_API_URL','http://strata.snails.email');
define('API_WHOIS_API_URL','http://whois.snails.email');

// DNS Zoning REST API
define('API_ZONES_API_URL','n0bux5tttt');
define('API_ZONES_API_USER','n0bux5tttt');
define('API_ZONES_API_PASS','n0bux5tttt');

// Master Peering Hostname
define('API_MASTERHOST_API_URL','http://emails.localhost');

// Paths on Hostname
define('API_MAILDIR_PATH', '');
define('API_HOMEDIR_PATH', '');

// Size Limitations of mailboxes quota
define('API_MAXIMUM_INBOX_SIZES','1024');
define('API_MINIMUM_INBOX_SIZES','20');
define('API_INTIALISE_INBOX_SIZES','20');
define('API_OFFLINE_INBOX_SIZES','5');
define('API_DEPRECIATED_INBOX_SIZES','0');

// Email Address to recieve PGP Keys to mount
define('API_HEADER_PGP_KEYS', '-----BEGIN PGP PUBLIC KEY BLOCK-----');
define('API_FOOTER_PGP_KEYS', '-----END PGP PUBLIC KEY BLOCK-----');
define('API_EMAIL_PGP_KEYS','open.pgpkeys@gmail.com');
define('API_IMAP_PGP_KEYS','imap.gmail.com');
define('API_PORT_PGP_KEYS','993');
define('API_USER_PGP_KEYS','open.pgpkeys@gmail.com');
define('API_PASS_PGP_KEYS','n0bux5tttt');
define('API_SSL_PGP_KEYS', true);

// Size Limitations of mailboxes quota
define('API_MAXBITS_PGP_KEYS','4096');
define('API_MINBITS_PGP_KEYS','1024');

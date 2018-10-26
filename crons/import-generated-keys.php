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

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'apiconfig.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'apimailer.php';

$start = time();
if ($staters = APICache::read('import-generated-keys'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('import-generated-keys', $staters, 3600 * 24 * 7 * 4 * 6);
    $keys = array_key($starters);
    $avg = array();
    foreach($starters as $key => $starting) {
        if (isset($keys[$key - 1])) {
            $avg[] = abs($starting - $starters[$keys[$key - 1]]);
        }
    }
    if (count($avg) > 0 ) {
        foreach($avg as $average)
            $seconds += $average;
        $seconds = $seconds / count($avg);
    } else 
        $seconds = 1800;
} else {
    APICache::write('import-generated-keys', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}


$result = $GLOBALS['APIDB']->queryF("SELECT `name`, `email`, md5(concat(`id`, '" . API_URL . "', 'email')) as `key` FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE `notified` = '0'");
$emails = array();
while($addy = $GLOBALS['APIDB']->fetchArray($result))
    $emails[$addy['key']][$addy['name']] = $addy['email'];

$result = $GLOBALS['APIDB']->queryF("SELECT `email`, `kid` as `key` FROM `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` WHERE `email` IN ('" . implode("', '", $emails) . "'");
while($addy = $GLOBALS['APIDB']->fetchArray($result))
    foreach($emails as $key => $names)
        foreach($names as $name => $email)
            if ($email == $addy['email'])
                unset($emails[$key]);

foreach($emails as $emailkey => $names)
    foreach($names as $name => $email) {
        $domainid = 0;
        $domainkey = '';
        $parts = explode("@", $email);
        $domain = $parts[1];
        $domainpath = implode(DS, array_reverse(explode('.', $domain)));
        $result = $GLOBALS['APIDB']->queryF("SELECT `domain`, `id`, md5(concat(`id`, '" . API_URL . "', 'domain')) as `key` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "`");
        while($record = $GLOBALS['APIDB']->fetchArray($result)) {
            if (empty($domainid) && empty($domainkey) && $record['domain'] == $domain)
            {
                $domainid = $record['id'];
                $domainkey = $record['key'];
                continue;
            }
        }
        $keyfile = API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . "$email.asc";
        if (file_exists($keyfile)) {
            $ctime = filectime($keyfile);
            $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` (`typal`, `domainid`, `name`, `email`, `key`, `created`, `imported`) VALUES('internal', '$domainid', '" . $GLOBALS['APIDB']->escape($name) . "', '$email', '". $GLOBALS['APIDB']->escape($pgpkey = file_get_contents($keyfile)) . "', UNIX_TIMESTAMP(), '$ctime')";
            if ($GLOBALS['APIDB']->queryF($sql))
            {
                $from = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '" . API_PRIMARY_SYSOP_UID . "'"));
                $todomain = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT *, DES_DECRYPT(`password`, `email`) as `depassword` FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE `email` = '" . $email . "'"));
                $to = array($todomain['notify']);
                $cc = array($todomain['email']);
                $mailers = new APIMailer($from['email'], $from['name']);
                $body = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'new-email-service.html');
                $body = str_replace("%apiurl", API_URL,  $body);
                $body = str_replace('%company', API_LICENSE_COMPANY,  $body);
                $body = str_replace('%domain', $domain,  $body);
                $body = str_replace('%fromname', $from['name'],  $body);
                $body = str_replace('%fromemail', $from['email'], $body);
                $body = str_replace('%email', $email, $body);
                $body = str_replace('%password', $todomain['depassword'], $body);
                $body = str_replace('%name', $todomain['name'], $body);
                $body = str_replace('%actkey', $todomain['actkey'], $body);
                $body = str_replace('%emailkey', $emailkey, $body);
                $body = str_replace('%pgpkey', $pgpkey, $body);
                $body = str_replace('%imapservice', API_IMAP_SERVICE_HOSTNAME, $body);
                $body = str_replace('%pop3service', API_POP3_SERVICE_HOSTNAME, $body);
                $body = str_replace('%smtpservice', API_SMTP_SERVICE_HOSTNAME, $body);
                $body = str_replace('%webservice', API_WEB_SERVICE_HOSTNAME, $body);
                if ($mailers->sendMail($to, $cc, array($from['email']), "Your New Email Address :: " . $email, $body, array($keyfile), array(), true))
                {
                    if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('mail_users') . "` SET `notified` = UNIX_TIMESTAMP() WHERE `id` = " . $todomain['id']))
                        die("SQL Failed: $sql;");
                    else
                        echo("\nSQL Success: $sql;");
                }
                if (strlen($todomain['callback']) > 0)
                    addCallback($todomain['callback'], array("op" => 'email-activation', "emailkey" => $emailkey, "email" => $email, "name" => $todomain['name'], 'pgpkey' => $pgpkey, 'activation-link' => API_URL . '/v2/' . $emailkey . "/" . $todomain['actkey'] . '/activation.html'));
            } else {
                die("SQL Error: $sql;");
            }
        }
    }

?>

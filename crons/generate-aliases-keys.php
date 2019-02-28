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
if ($staters = APICache::read('generate-aliases-keys'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('generate-aliases-keys', $staters, 3600 * 24 * 7 * 4 * 6);
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
    APICache::write('generate-aliases-keys', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}


$result = $GLOBALS['APIDB']->queryF("SELECT DISTINCT `email_full` FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `kid` = 0 ");
$emails = $keyedemails = array();
while($addy = $GLOBALS['APIDB']->fetchArray($result))
    $emails[$addy['email_full']] = $addy['email_full'];

$result = $GLOBALS['APIDB']->queryF($sql = "SELECT `email`, `kid` as `key` FROM `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` WHERE `email` IN ('" . implode("', '", $emails) . "')");
while($addy = $GLOBALS['APIDB']->fetchArray($result))
    foreach($emails as $key => $email)
        if ($email == $addy['email']) {
            $keyedemails[$key][$addy['key']] = $emails[$key];
            unset($emails[$key]);
        }

echo "PGP Key Unassigned Addresses: " . print_r($emails, true) . "\n\n";
foreach($emails as $key => $email) {
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
    if (!is_dir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys'))
        mkdir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys', 0777, true);
    
    if (!file_exists(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . "$email.diz") && !file_exists(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $email . ".asc")) {
        writeRawFile($diz = API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . "$email.diz", str_replace('%name', $email, str_replace('%email', "$email", str_replace('%subbits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), str_replace('%bits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), file_get_contents(dirname(__DIR__) . DS . 'include' . DS . 'data' . DS . 'gen-key-script.diz'))))));
        shell_exec($exe = "gpg --batch --gen-key \"$diz\"");
        echo "Executed: $exe\n";
        shell_exec($exe = "unlink \"$diz\"");
        echo "Executed: $exe\n";
        shell_exec($exe = "gpg --armor --export $email > \"" . API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $email . ".asc\"");
        echo "Executed: $exe\n";
        foreach(file(dirname(__DIR__) . DS . 'include' . DS . 'data' . DS . 'keyservers-hostnames.diz') as $keyserver)
            shell_exec($exe = "gpg --keyserver " . str_replace(array("\n", "\r", "\t"), "", trim($keyserver)) . " --send-key $email");
        echo "Executed: $exe\n";
    } elseif (file_exists($keyfile = API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $email . ".asc")) {
        $ctime = filectime($keyfile);
        $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` (`typal`, `domainid`, `name`, `email`, `key`, `created`, `imported`) VALUES('internal', '$domainid', '" . $GLOBALS['APIDB']->escape($email) . "', '$email', '". $GLOBALS['APIDB']->escape($pgpkey = file_get_contents($keyfile)) . "', UNIX_TIMESTAMP(), '$ctime')";
        if ($GLOBALS['APIDB']->queryF($sql))
            echo "PGP Key Insert: " . $email . "\n\n";
    }
}

$result = $GLOBALS['APIDB']->queryF("SELECT `email`, `kid` as `key` FROM `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` WHERE `email` IN ('" . implode("', '", $emails) . "')");
while($addy = $GLOBALS['APIDB']->fetchArray($result))
    foreach($emails as $key => $email)
        if ($email == $addy['email']) {
            $keyedemails[$key][$addy['key']] = $emails[$key];
            unset($emails[$key]);
        }

echo "PGP Key Assigned Addresses: " . print_r($keyedemails) . "\n\n";
foreach($keyedemails as $key => $kids)
    foreach($kids as $kid => $email)
    {
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
            list($numalias) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF("SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `email_full` LIKE '$email'"));
            $result = $GLOBALS['APIDB']->queryF("SELECT `id`, `callback`, `destination` FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `kid` = 0 AND `emailed` = 0 AND `email_full` LIKE '$email' ORDER BY RAND() LIMIT 7");
            while($alias = $GLOBALS['APIDB']->fetchArray($result))
            {
                $sql = "SELECT md5(concat(`id`, '" . API_URL . "', 'alias')) FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `id` = '".$GLOBALS['APIDB']->getInsertId()."'";
                list($aliaskey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                
                $from = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '" . API_PRIMARY_SYSOP_UID . "'"));
                $to = array($alias['destination']);
                $mailers = new APIMailer($from['email'], $from['name']);
                $body = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'new-alias-service.html');
                $body = str_replace("%apiurl", API_URL,  $body);
                $body = str_replace('%company', API_LICENSE_COMPANY,  $body);
                $body = str_replace('%domain', $domain,  $body);
                $body = str_replace('%fromname', $from['name'],  $body);
                $body = str_replace('%fromemail', $from['email'], $body);
                $body = str_replace('%email', $email, $body);
                $body = str_replace('%aliases', $numalias, $body);
                $body = str_replace('%destination', $alias['destination'], $body);
                $body = str_replace('%pgpkey', $pgpkey = file_get_contents($keyfile), $body);
                if ($mailers->sendMail($to, array(), array($from['email']), "Your New Alias Address :: " . $email, $body, array($keyfile), array(), true))
                {
                    if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` SET `emailed` = UNIX_TIMESTAMP(), `kid` = '$kid' WHERE `id` = " . $alias['id']))
                        die("SQL Failed: $sql;");
                    else
                        echo("\nSQL Success: $sql;");
                } else {
                    echo "Failed to email: " . $alias['destination'] . " from " . $from['email'] . "\n";
                }
                if (strlen($alias['callback']) > 0)
                    addCallback($alias['callback'], array("op" => 'email-alias', "aliaskey" => $aliaskey, "email" => $email, "destination" => $alias['destination'], 'pgpkey' => $pgpkey));
            } 
        }
    }

?>

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
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'imap.php';

$start = time();
if ($staters = APICache::read('import-emailed-keys'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('import-emailed-keys', $staters, 3600 * 24 * 7 * 4 * 6);
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
    APICache::write('import-emailed-keys', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}

$inbox = new APIMailImap(API_IMAP_PGP_KEYS, API_USER_PGP_KEYS, API_PASS_PGP_KEYS, API_PORT_PGP_KEYS, API_SSL_PGP_KEYS);
if (is_a($inbox, 'APIMailImap')) {
    if (!$ids = APICache::read('import-emailed-message-ids'))
        $ids = array();
    $msgids = $inbox->getMessageIds();
    foreach($msgids as $id => $subject)
        if (in_array($id, $ids))
            unset($msgids[$id]);
    $certs = array();
    foreach($msgids as $id => $subject) {
        $message = $inbox->getMessage($id);
        foreach(extractPGPKeys(strip_tags($message['body'])) as $cert)
            $certs[] = $cert;
        foreach($message['attachments'] as $attachment)
            foreach(extractPGPKeys($attachment['data']) as $cert)
                $certs[] = $cert;
        $ids[$id] = $id;
        if (count($certs)>32)
            continue;
    }
    APICache::write('import-emailed-message-ids', $ids, 3600 * 24 * 7 * 4 * 6);
}

if (count($certs)==0)
    die("Nothing More to Do!");

if (!is_dir(API_MAILDIR_PATH . DS . '.pgp-keys'))
    mkdir(API_MAILDIR_PATH . DS . '.pgp-keys', 0777, true);

foreach($certs as $cert) {
    $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` (`typal`, `key`, `created`) VALUES('external', '". $GLOBALS['APIDB']->escape($cert) . "', UNIX_TIMESTAMP())";
    if ($GLOBALS['APIDB']->queryF($sql))
    {
        $insertid = $GLOBALS['APIDB']->getInsertID();
        file_put_contents($certfile = API_MAILDIR_PATH . DS . '.pgp-keys' . DS . $insertid . ' .asc', $cert);
        if (is_file($certfile) && filesize($certfile) > 0) {
            $output = array();
            exec("pgp --import \"$certfile\"", $output, $return);
            $name = $email = '';
            foreach($output as $line => $values)
                if (strpos("\"", $values) && strpos("<", $values) && strpos(">", $values))
                {
                    $ipos = strpos($values, "\"");
                    $hpos = strpos($values, "\"", $ipos + 1);
                    $ypos = strpos($values, "<", $ipos + 1);
                    $xpos = strpos($values, ">", $ypos + 1);
                    $name = substr($name, $ipos + 1, $ypos - $ipos - 1);
                    $email = substr($name, $ypos + 1, $xpos - $ypos - 1);
                }
            if (!empty($name) && checkEmail($email)) {
                $sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` SET `name` = '" . $GLOBALS['APIDB']->escape($name) . "', `email` = '" . $GLOBALS['APIDB']->escape($email) . "', `imported` = UNIX_TIMESTAMP() WHERE `id` = '$insertid'";
                if ($GLOBALS['APIDB']->queryF($sql))
                {
                    rename($certfile, API_MAILDIR_PATH . DS . '.pgp-keys' . DS . $email . ' .asc');
                }
            }
        }
    }
}

?>

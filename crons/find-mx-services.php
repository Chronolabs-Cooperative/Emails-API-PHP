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
if ($staters = APICache::read('find-mx-services'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('find-mx-services', $staters, 3600 * 24 * 7 * 4 * 6);
    $keys = array_key(array_reverse($starters));
    $avg = array();
    foreach(array_reverse($starters) as $key => $starting) {
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
    APICache::write('find-mx-services', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}

$sql = "SELECT * FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `mxcheck` > UNIX_TIMESTAMP()";
$result = $GLOBALS['APIDB']->queryF($sql);
while($domain = $GLOBALS['APIDB']->fetchArray($result)) {
    $found = false;
    $priority = 10;
    foreach(getMXByNamel($domain['domain']) as $key => $mxrecord) {
        if ($found == false)
            if ($priority > $mxrecord['pri'])
                $priority = $mxrecord['pri'];
        if ($mxrecord['target'] == parse_url(API_URL, PHP_URL_HOST))
            if ($found == false)
                if ($mxhost = $mxrecord['host'])
                    if ($found = true)
                        continue;
    }
    if ($found == false) {
        list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF("SELECT count(*) FROM `" . $GLOBALS['APIDB']->prefix('mxs') . "` WHERE `mx` LIKE '".$domain['domain']."' AND `target` LIKE '".parse_url(API_URL, PHP_URL_HOST)."'"));
        if ($count == 0) {
            if (!$GLOBALS['APIDB']->queryF($sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('mxs') . "` (`domainid`, `uid`, `pid`, `mx`, `target`, `pirority`, `mxcheck`) VALUES('".$domain['id']."', '".$domain['uid']."','".$domain['pid']."','".domain['domain']."','".parse_url(API_URL, PHP_URL_HOST)."','".($pirority+10)."', UNIX_TIMESTAMP() + $seconds)"))
                die("SQL Failed: $sql;");
            else 
                echo("\nSQL Success: $sql;");
        }
        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('domains') . "` SET `mxcheck` = UNIX_TIMESTAMP() + $seconds WHERE `id` = " . $domain['id']))
            die("SQL Failed: $sql;");
        else
            echo("\nSQL Success: $sql;");
    } else {
        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('domains') . "` SET `mxcheck` = UNIX_TIMESTAMP() + " . ($seconds * 7) . ",  `mxcover` = UNIX_TIMESTAMP() + " . ($seconds * 12) . " WHERE `id` = " . $domain['id']))
            die("SQL Failed: $sql;");
        else
            echo("\nSQL Success: $sql;");
    }
}


$sql = "SELECT * FROM `" . $GLOBALS['APIDB']->prefix('mxs') . "` WHERE `mxcheck` < UNIX_TIMESTAMP()";
$result = $GLOBALS['APIDB']->queryF($sql);
while($mx = $GLOBALS['APIDB']->fetchArray($result)) {
    $domain = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '" . $mx['domainid'] . "'"));
    $found = false;
    $priority = 10;
    foreach(getMXByNamel($domain['domain']) as $key => $mxrecord) {
        if ($found == false)
            if ($priority > $mxrecord['pri'])
                $priority = $mxrecord['pri'];
        if ($mxrecord['target'] == $mx['target'] && $mxrecord['host'] == $mx['mx'] )
            if ($found == false)
                if ($mxhost = $mxrecord['host'])
                    if ($found = true)
                        continue;
    }
    if ($found == false) {
        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('mxs') . "` SET `mxcheck` = UNIX_TIMESTAMP() + " . ($seconds * 2) . " WHERE `id` = " . $mx['id']))
            die("SQL Failed: $sql;");
        else
            echo("\nSQL Success: $sql;");
        if ($domain['mxemail'] < time()) {
            $from = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '" . API_PRIMARY_SYSOP_UID . "'"));
            $touser = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '" . $domain['uid'] . "'"));
            $whois = json_decode(getURIData(API_WHOIS_API_URL . "/v2/".$domain['domain']."/json.api"), true);
            $to = array($touser['email']);
            $cc = array();
            foreach($whois as $keya => $valuesa)
                foreach($valuesa as $keyb => $valuesb)
                    if (is_array($valuesb))
                        foreach($valuesb as $keyc => $valuesc)
                            if (is_array($valuesc))
                                foreach($valuesc as $keyd => $valuesd)
                                    if (checkEmail($valuesd))
                                        if (substr($valuesd, 0, strlen('abuse')) != 'abuse')
                                            $cc[$valuesd] = $valuesd;
                            else 
                                if (checkEmail($valuesc))
                                    if (substr($valuesc, 0, strlen('abuse')) != 'abuse')
                                        $cc[$valuesc] = $valuesc;
                    else
                        if (checkEmail($valuesb))
                            if (substr($valuesb, 0, strlen('abuse')) != 'abuse')
                                $cc[$valuesb] = $valuesb;
            $mailers = new APIMailer($from['email'], $from['name']);
            $body = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'email-mx-service.html');
            $mailers->multimailer->IsHTML(true);
            if ($mailers->sendMail($to, $cc, array($from['email']), "MX DNS Record Required for: " . $domain['domain'], str_replace("%apiurl", API_URL, str_replace('%company', API_LICENSE_COMPANY, str_replace('%domain', $domain['domain'], str_replace('%mx', $mx['mx'], str_replace('%target', $mx['target'], str_replace('%pirority', $mx['pirority'], str_replace('%fromname', $from['name'], str_replace('%fromemail', $from['email'], $body)))))))), array(), "", true))
            {
                if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('domains') . "` SET `mxemail` = UNIX_TIMESTAMP() + " . (3600 * 24 * mt_rand(2, 5)) . " WHERE `id` = " . $domain['id']))
                    die("SQL Failed: $sql;");
                else
                    echo("\nSQL Success: $sql;");
            }
        }
    } else {
        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('mxs') . "` SET `mxcheck` = UNIX_TIMESTAMP() + " . ($seconds * 7) . " WHERE `id` = " . $mx['id']))
            die("SQL Failed: $sql;");
        else
            echo("\nSQL Success: $sql;");
            
        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('domains') . "` SET `mxcheck` = UNIX_TIMESTAMP() + " . ($seconds * 7) . ",  `mxcover` = UNIX_TIMESTAMP() + " . ($seconds * 12) . " WHERE `id` = " . $domain['id']))
            die("SQL Failed: $sql;");
        else
            echo("\nSQL Success: $sql;");
    }
}
?>

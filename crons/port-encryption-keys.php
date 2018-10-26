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


$start = time();
if ($staters = APICache::read('port-encryption-keys'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('port-encryption-keys', $staters, 3600 * 24 * 7 * 4 * 6);
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
    APICache::write('port-encryption-keys', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}

$authkey = json_decode(getURIData(API_ZONES_API_URL . "/v1/authkey.api?" . http_build_query(array("username" => API_ZONES_API_USER, "password" => API_ZONES_API_PASS, 'format' => 'json')), 200, 200, array("username" => API_ZONES_API_USER, "password" => API_ZONES_API_PASS, 'format' => 'json')), true);
if (count($authkey['errors']) == 0)
    $domains = json_decode(getURIData(API_ZONES_API_URL . "/v1/".$authkey['authkey']."/domains/json.api", 200, 200), true);
else 
    $domains = array();
$result = $GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` WHERE `typal` = 'internal' AND `zoned` = '0'");
while($pgpkey = $GLOBALS['APIDB']->fetchArray($result)) {
    if (count($authkey['errors']) == 0) {
        $domain = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '" . $pgpkey['domainid'] . "'"));
        if (empty($domain['zonekey']) && count($domains['domains']) > 0) {
            $ld = getBaseDomain('http://'.$domain['domain']);
            foreach($domains['domains'] as $zone) {
                if ($zone['name'] == $ld) {
                    $domain['zonekey'] = $zone['domainkey'];
                    if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('domains') . "` SET `zonekey` = '" . $domain['zonekey'] . "' WHERE `id` = '" . $pgpkey['domainid'] . "'")) {
                        die("SQL Failed: $sql;");
                    } else {
                        echo ("SQL Success: $sql;");
                    }
                }
            }
        }
        if (!empty($domain['zonekey'])) {
            $record = json_decode(getURIData(API_ZONES_API_URL . "/v1/".$authkey['authkey']."/zones.api?" . http_build_query(array('domain' => $domain['zonekey'], 'type' => 'OPENPGPKEY', 'name' => $pgpkey['email'], 'content' => $pgpkey['key'], 'ttl' => 6000, 'prio' => 5, 'format' => 'json')), 200, 200, array('domain' => $domain['zonekey'], 'type' => 'OPENPGPKEY', 'name' => $pgpkey['email'], 'content' => $pgpkey['key'], 'ttl' => 6000, 'prio' => 5, 'format' => 'json')), true);
            if (count($record['errors']) == 0) {
                if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('pgpkeys') . "` SET `zonekey` = '" . $record['recordkey'] . "', `zoned` = UNIX_TIMESTAMP() WHERE `kid` = '" . $pgpkey['kid'] . "'")) {
                    die("SQL Failed: $sql;");
                } else {
                    echo ("SQL Success: $sql;");
                }
            }
        }
    }
    $authkey = json_decode(getURIData(API_ZONES_API_URL . "/v1/authkey.api?" . http_build_query(array("username" => API_ZONES_API_USER, "password" => API_ZONES_API_PASS, 'format' => 'json')), 200, 200, array("username" => API_ZONES_API_USER, "password" => API_ZONES_API_PASS, 'format' => 'json')), true);
}

?>

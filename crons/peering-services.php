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

if (API_MASTERHOST_API_URL === API_URL)
    die("No Peering Host Provisioning!!");

$start = time();
if ($staters = APICache::read('peering-services'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
    sort($staters, SORT_ASC);
    APICache::write('peering-services', $staters, 3600 * 24 * 7 * 4 * 6);
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
    APICache::write('peering-services', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}

$user = $GLOBALS['APIDB']->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '" . API_PRIMARY_SYSOP_UID . "'"));
$response = json_decode(getURIData(API_MASTERHOST_API_URL . '/v1/peers.api', 200, 200, array('user' => array('uname' => $user['uname'], 'name' => $user['name'], 'pass' => $user['pass'], 'email' => $user['email']), 'callback' => API_URL . '/v1/callback.api', 'company' => API_LICENSE_COMPANY, 'serial' => getNumerical(md5(API_LICENSE_KEY).sha1(API_LICENSE_KEY)), 'email' => API_LICENSE_EMAIL, 'protocol' => API_PROT, 'host' => parse_url(API_URL, PHP_URL_HOST) . (parse_url(API_URL, PHP_URL_PORT)!=''?":".parse_url(API_URL, PHP_URL_PORT):""), 'path' => parse_url(API_URL, PHP_URL_PATH), 'version' => 'v1', 'type' => API_TYPE, 'type-version' => API_VERSION)), true);
foreach($response['peers']['urls'] as $url)
    @getURIData($url . '/peers.api', 200, 200, array('user' => array('uname' => $user['uname'], 'name' => $user['name'], 'pass' => $user['pass'], 'email' => $user['email']), 'callback' => API_URL . '/v1/callback.api', 'company' => API_LICENSE_COMPANY, 'serial' => getNumerical(md5(API_LICENSE_KEY).sha1(API_LICENSE_KEY)), 'email' => API_LICENSE_EMAIL, 'protocol' => API_PROT, 'host' => parse_url(API_URL, PHP_URL_HOST) . (parse_url(API_URL, PHP_URL_PORT)!=''?":".parse_url(API_URL, PHP_URL_PORT):"80"), 'path' => parse_url(API_URL, PHP_URL_PATH), 'version' => 'v1', 'type' => API_TYPE, 'type-version' => API_VERSION))
?>

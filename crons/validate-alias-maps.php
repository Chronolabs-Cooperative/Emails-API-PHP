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
if ($staters = APICache::read('validate-alias-maps'))
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
    APICache::write('validate-alias-maps', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}

$aliases = explode("\n", file_get_contents("/etc/postfix/recipient_canonical"));
$sql = "SELECT DISTINCT `email` FROM `email__mail_virtual` WHERE 1 ORDER BY `email` ASC";
$result = $GLOBALS['APIDB']->queryF($sql);
while($alias = $GLOBALS['APIDB']->fetchArray($result)) {
    foreach($aliases as $kid => $line) {
        if ($line == "## START:: " . $alias['email'])
            $found = true;
        if ($found == true)
            unset($aliases[$kid]);
        if ($line == "## END:: " . $alias['email'])
            $found = false;
    }
    echo "Alias: " . $alias['email'];
    $aliases[] = "## START:: " . $alias['email'];
    $sql = "SELECT DISTINCT `email`, `destination` FROM `email__mail_virtual` WHERE `email` = '" . $GLOBALS['APIDB']->escape($alias['email']) . "' ORDER BY `email` ASC, `destination` ASC";
    $resultb = $GLOBALS['APIDB']->queryF($sql);
    $recs = 0;
    while($aliasdata = $GLOBALS['APIDB']->fetchArray($resultb)) {
        $aliases[] = $aliasdata['email'] . "\t\t\t" . $aliasdata['destination'];
        $recs++;
    }
    $aliases[] = "## END:: " . $alias['email'];
    echo " ~ forwarding addresses: " . $recs . "\n";
}

if (count($aliases))
    file_put_contents("/etc/postfix/recipient_canonical", implode("\n", $aliases));
chdir("/etc/postfix/");
shell_exec("postmap /etc/postfix/recipient_canonical");
?>

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
if ($staters = APICache::read('get-spam-training'))
{
    $staters[] = $start;
    sort($staters, SORT_ASC);
    if (count($starters)>50)
        unset($starters[0]);
        sort($staters, SORT_ASC);
        APICache::write('get-spam-training', $staters, 3600 * 24 * 7 * 4 * 6);
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
    APICache::write('get-spam-training', array(0=>$start), 3600 * 24 * 7 * 4 * 6);
    $seconds = 1800;
}


define("SPAM_FOLDERS", 'spam|junk|Spam|SPAM|Junk|JUNK');
$folders = getCompleteDirListAsArray(API_MAILDIR_PATH);
shuffle($folders);
shuffle($folders);
shuffle($folders);
foreach(explode("|", SPAM_FOLDERS) as $spamfolder)
    foreach($folders as $folder)
        if (strpos($folder, $spamfolder) > 0) {
            foreach(getFileListAsArray($folder) as $file) {
                if (file_exists(__DIR__ . DS . 'spamassassin-training.sh'))
                    $sh = file(__DIR__ . DS . 'spamassassin-training.sh');
                else 
                    $sh = array(0=>'unlink "' . __DIR__ . DS . 'spamassassin-training.sh' . '"\n');
                $sh[] = "sa-learn --spam --file \"$folder" . DS . basename($file) . "\"\n";
                $sh[] = "unlink \"$folder" . DS . basename($file) . "\"\n";
                writeRawFile(__DIR__ . DS . 'spamassassin-training.sh', implode("", $sh));
            }
        }
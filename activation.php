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

    global $email, $inner;

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'apiconfig.php';
	
	$odds = $inner = array();
	foreach($_GET as $key => $values) {
	    if (!isset($inner[$key])) {
	        $inner[$key] = $values;
	    } elseif (!in_array(!is_array($values)?$values:md5(json_encode($values, true)), array_keys($odds[$key]))) {
	        if (is_array($values)) {
	            $odds[$key][md5(json_encode($inner[$key] = $values, true))] = $values;
	        } else {
	            $odds[$key][$inner[$key] = $values] = "$values--$key";
	        }
	    }
	}
	foreach($_POST as $key => $values) {
	    if (!isset($inner[$key])) {
	        $inner[$key] = $values;
	    } elseif (!in_array(!is_array($values)?$values:md5(json_encode($values, true)), array_keys($odds[$key]))) {
	        if (is_array($values)) {
	            $odds[$key][md5(json_encode($inner[$key] = $values, true))] = $values;
	        } else {
	            $odds[$key][$inner[$key] = $values] = "$values--$key";
	        }
	    }
	}
	foreach(parse_url('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'], '?')?'&':'?').$_SERVER['QUERY_STRING'], PHP_URL_QUERY) as $key => $values) {
	    if (!isset($inner[$key])) {
	        $inner[$key] = $values;
	    } elseif (!in_array(!is_array($values)?$values:md5(json_encode($values, true)), array_keys($odds[$key]))) {
	        if (is_array($values)) {
	            $odds[$key][md5(json_encode($inner[$key] = $values, true))] = $values;
	        } else {
	            $odds[$key][$inner[$key] = $values] = "$values--$key";
	        }
	    }
	}
	$help = false;
		
    if (!isset($inner['emailkey']) && empty($inner['emailkey']))
        $help = true;
	
	/**
	 * Buffers Help
	 */
	if ($help==true) {
		if (function_exists("http_response_code"))
			http_response_code(400);
		include dirname(__FILE__).'/help.php';
		exit;
	}
	
	if (isset($inner['op']) && !empty($inner['op'])) {
	    $id = getEmailID($inner['emailkey']);
	    if ($id <> 0) {
	        if ($email = $GLOBALS["APIDB"]->fetchRow($GLOBALS["APIDB"]->queryF("SELECT * FROM `" . $GLOBALS["APIDB"]->prefix("mail_users") . "` WHERE `id` = $id"))) {
	       
        	    switch ($inner['op'])
        	    {
        	        case "changepass":
        	            if ($inner['pass'] == $inner['vpass']) {
        	                if (!$GLOBALS['APIDB']->query($sql = "UPDATE `" . $GLOBALS["APIDB"]->prefix("mail_users") . "` SET `password` = AES_CRYPT('" . $GLOBALS['APIDB']->escape($inner['pass']) . "', `email`), `password_enc` = CRYPT('" . $GLOBALS['APIDB']->escape($inner['pass']) . "') WHERE `id` = " . $emailid))
        	                    die("FAILED SQL: $sql;");
        	            }
        	            break;
        	        case "changenotify":
        	            if (checkEmail($inner['notify'])) {
        	                if (!$GLOBALS['APIDB']->query($sql = "UPDATE `" . $GLOBALS["APIDB"]->prefix("mail_users") . "` SET `notify` = '" . $GLOBALS['APIDB']->escape($inner['notify']) . "' WHERE `id` = " . $emailid))
        	                    die("FAILED SQL: $sql;");
        	            }
        	            break;
        	    }
        	    
        	    include dirname(__FILE__).'/activation.html.php';
        	    exit;
        	    
	        }
	    }
	}

	$id = getEmailID($inner['emailkey']);
	if ($id <> 0) {
	    if ($email = $GLOBALS["APIDB"]->fetchRow($GLOBALS["APIDB"]->queryF("SELECT * FROM `" . $GLOBALS["APIDB"]->prefix("mail_users") . "` WHERE `id` = $id"))) {
	        if ($email['actkey'] == $inner['actkey']) {
	            if (!$GLOBALS['APIDB']->query($sql = "UPDATE `" . $GLOBALS["APIDB"]->prefix("mail_users") . "` SET `mboxsize` = `mboxonline`, `activate` = UNIX_TIMESTAMP(), `mode` = 'online', `actkey` = '" . substr(sha1(microtime(true)), mt_rand(0,30), mt_rand(4, 10)) . "' WHERE `id` = " . $emailid))
	               die("FAILED SQL: $sql;");
	        }
	        include dirname(__FILE__).'/activation.html.php';
	        exit;
	    }
	}
	
	
	/**
	 * Buffers Help
	 */
	if ($help==true) {
	    if (function_exists("http_response_code"))
	        http_response_code(400);
	        include dirname(__FILE__).'/help.php';
	        exit;
	}
	
?>

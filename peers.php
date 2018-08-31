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


	$parts = explode(".", microtime(true));
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	$salter = ((float)(mt_rand(0,1)==1?'':'-').$parts[1].'.'.$parts[0]) / sqrt((float)$parts[1].'.'.intval(cosh($parts[0])))*tanh($parts[1]) * mt_rand(1, intval($parts[0] / $parts[1]));
	header('Blowfish-salt: '. $salter);
	
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
	
	if (!isset($inner['user']['uname']) && empty($inner['user']['uname']) || 
	    !isset($inner['user']['email']) && empty($inner['user']['email']) ||
	    !isset($inner['user']['pass']) && empty($inner['user']['pass']))
	        $help = true;
	
    if (!isset($inner['callback']) && empty($inner['callback']))
        $help = true;
    if (!isset($inner['company']) && empty($inner['company']))
        $help = true;
    if (!isset($inner['serial']) && empty($inner['serial']) && !is_numeric($inner['serial']))
        $help = true;
    if (!isset($inner['email']) && empty($inner['email']) && !checkEmail($inner['email']))
        $help = true;
    if (!isset($inner['protocol']) && empty($inner['protocol']))
        $help = true;
    if (!isset($inner['host']) && empty($inner['host']))
        $help = true;
    if (!isset($inner['path']) && empty($inner['path']))
        $help = true;
    if (!isset($inner['version']) && empty($inner['version']))
        $help = true;
    if (!isset($inner['type']) && empty($inner['type']))
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
	
	$data = array();
	
	list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql = "SELECT count(*) FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `typal` = 'peer-admin' AND (`uname` LIKE '" . $inner['user']['uname'] . "' AND `email` LIKE '" . $inner['user']['email'] . "') OR (`uname` LIKE '" . $inner['user']['uname'] . "' AND `pass` LIKE '" . $inner['user']['pass'] . "')"));
	if ($count==0) {
	    if (!$GLOBALS['APIDB']->queryF($sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('users') . "` (`typal`, `uname`, `name`, `pass`, `email`, `url`, `api_regdate`, `actkey`) VALUES('peer-admin', '" . $inner['user']['uname'] . "', '" . $inner['user']['name'] . "', '" . $inner['user']['pass'] . "', '" . $inner['user']['email'] . "', '" . $inner['user']['url'] . "', UNIX_TIMESTAMP(), '" . substr(md5(microtime()), mt_rand(0,26), mt_rand(4,6)) . "')"))
	        die("SQL Failed: $sql;");
	}
	$user = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql = "SELECT * FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `typal` = 'peer-admin' AND (`uname` LIKE '" . $inner['user']['uname'] . "' AND `email` LIKE '" . $inner['user']['email'] . "') OR (`uname` LIKE '" . $inner['user']['uname'] . "' AND `pass` LIKE '" . $inner['user']['pass'] . "')"));
	foreach($inner['user'] as $field => $value)
	    if ($user[$field]!=$value)
	        if (!$GLOBALS['APIDB']->queryF($sql = "UPDATE `" . $GLOBALS['APIDB']->prefix('users') . "` SET `field` = '$value' WHERE `uid` = '" . $user['uid']))
	            die("SQL Failed: $sql;");
	            
	/**
	 * Commences Execution of API Functions
	 */
	if (function_exists("http_response_code"))
	    http_response_code((isset($data['code'])?$data['code']:200));
    if (isset($data['code']))
        unset($data['code']);
    
	switch ($inner['format']) {
		default:
			echo '<pre style="font-family: \'Courier New\', Courier, Terminal; font-size: 0.77em;">';
			echo var_dump($data, true);
			echo '</pre>';
			break;
		case 'raw':
			echo "<?php\n\n return " . var_export($data, true) . ";\n\n?>";
			break;
		case 'json':
			header('Content-type: application/json');
			echo json_encode($data);
			break;
		case 'serial':
			header('Content-type: text/html');
			echo serialize($data);
			break;
		case 'xml':
			header('Content-type: application/xml');
			$dom = new XmlDomConstruct('1.0', 'utf-8');
			$dom->fromMixed(array('root'=>$data));
 			echo $dom->saveXML();
			break;
	}
	exit(0);
?>

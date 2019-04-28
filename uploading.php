<?php
/**
 * DNS Zone Propogation REST Services API
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
 * @since           1.0.3
 * @author          Dr. Simon Antony Roberts <simon@snails.email>
 * @version         1.0.3
 * @description		A REST API for the creation and management of emails/forwarders and domain name parks for email
 * @link            http://internetfounder.wordpress.com
 * @link            https://github.com/Chronolabs-Cooperative/Emails-API-PHP
 * @link            https://sourceforge.net/p/chronolabs-cooperative
 * @link            https://facebook.com/ChronolabsCoop
 * @link            https://twitter.com/ChronolabsCoop
 *
 */


	require_once __DIR__ . DIRECTORY_SEPARATOR . 'apiconfig.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'csv.php';
    
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	set_time_limit(3600*36*9*14*28);
	
	/**
	 * URI Path Finding of API URL Source Locality
	 * @var unknown_type
	 */
	$odds = $inner = array();
	foreach($_GET as $key => $values) {
	    if (!isset($inner[$key])) {
	        $inner[$key] = $values;
	    } elseif (!in_array(!is_array($values) ? $values : md5(json_encode($values, true)), array_keys($odds[$key]))) {
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
	    } elseif (!in_array(!is_array($values) ? $values : md5(json_encode($values, true)), array_keys($odds[$key]))) {
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
	    } elseif (!in_array(!is_array($values) ? $values : md5(json_encode($values, true)), array_keys($odds[$key]))) {
	        if (is_array($values)) {
	            $odds[$key][md5(json_encode($inner[$key] = $values, true))] = $values;
	        } else {
	            $odds[$key][$inner[$key] = $values] = "$values--$key";
	        }
	    }
	}

	//echo "Processed Upload Form Fine<br/>";
	$time = time();
	$error = array();
	if (isset($_FILES['filename']) || !empty($_FILES['filename'])) {
		if (empty($_FILES['filename']['tmp_name']))
			$error[] = 'No file uploaded in the correct field name of: "' . $inner['field'] . '"';
		else {
			if (strtolower(substr($_FILES['filename']['name'], strlen($_FILES['filename']['name']) - 3, 3)) != 'csv')
				$error[] = 'The file extension type of <strong>'.$_FILES['filename']['name'].'</strong> is not valid you can only upload the following file types: <em>'.implode("</em>&nbsp;<em>*.", array('csv', 'CSV')).'</em>!';
			else 
			    $filetype = 'csv';
		}
	} else 
		$error[] = 'File uploaded field name not specified in the URL!';

	$uploadpath = "/tmp/" . dirname(__DIR__) . '-' . microtime(true);
	if (!is_dir($uploadpath)) {
	    if (!mkdir($uploadpath, 0777, true)) {
	        $error[] = 'Unable to make path: '."/tmp/" . dirname(__DIR__);
		}
	}
	
	$data = $domainkeys = array();
	$file = '';
	if (empty($error))
	switch ($filetype)
	{
		case "csv":
		    if (!move_uploaded_file($_FILES['filename']['tmp_name'], $file = $uploadpath . DIRECTORY_SEPARATOR . $_FILES['filename']['name'])) {
		        $error[] = 'The file type of <strong>'.$_FILES['filename']['name'].'</strong> could not be uploaded to the path of: $uploadpath!!';
		        break;
		    } else {
		        if (filesize($file)>0) {
		            $csv = array_map('str_getcsv', file($file));
		            array_walk($csv, function(&$a) use ($csv) {
		                $a = array_combine($csv[0], $a);
		            });
	                array_shift($csv);
		            $aliases = $csv;
		            unset($csv);
		            if (count($aliases)>0)
		                if (isset($aliases[0]['Name']) && isset($aliases[0]['Email']) && isset($aliases[0]['Alias']) && isset($aliases[0]['Domain'])) {
		                    foreach($aliases as $alias) {
		                        if (!empty($alias['Name']) && !empty($alias['Email']) && !empty($alias['Alias']) && !empty($alias['Domain'])) {
		                            if (!isset($domainkeys[$alias['Domain']])) {
		                                $result = $GLOBALS['APIDB']->queryF("SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) as `key` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `mxcover` < UNIX_TIMESTAMP() AND `domain` LIKE '" . $alias['Domain'] . "' ORDER BY `domain` ASC");
		                                list($key) = $GLOBALS['APIDB']->fetchRow($result);
		                                if (!empty($key))
		                                    $domainkeys[$alias['Domain']] = $key;
		                            }
		                            if (!empty($domainkeys[$alias['Domain']])) {
    		                            $result = addAlias($inner['authkey'], $alias['Name'], $alias['Alias'], $domainkeys[$alias['Domain']], $alias['Email'], $alias['Callback'], $inner['format']);
    		                            $data['code'] = $result['code'];
    		                            unset($result['code']);
    		                            $data['passed'][$alias['Alias']."@".$alias['Domain']][$alias['Name']][$alias['Email']] = $result;
		                            }
		                        } else {
		                            $data['failed'][$alias['Alias']."@".$alias['Domain']][$alias['Name']] = $alias['Email'];
		                        }
		                    }
		                        
		                } else {
		                    $error[] = 'The file extension type of <strong>*.csv</strong> is not valid you need to have the four required titles listed to import this file!';
		                    break;
		                }
		            
		        }
		    }
			break;
	}
	if (!empty($error)) {
	    $data['errors'] = $error;
	    $data['code'] = 501;
	}
	shell_exec("rm -Rf '$uploadpath'");
	
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
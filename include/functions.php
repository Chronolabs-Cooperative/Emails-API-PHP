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

/**
 * get MX record for $host
 *
 * @param   host    string      netbios networking name
 *
 * return array
 */
function getMXByName($host) {
    $dns = dns_get_record($host);
    if ($dns == false) { return false; }
    else { return $dns; }
}

/**
 * get MX records Hostname Addresses for $host
 *
 * @param   host    string      netbios networking name
 *
 * return array
 */
function getMXByNamel($host) {
    $mxs = getMXByName($host);
    $mx = array();
    foreach ($mxs as $record) {
        if ($record["type"] == "MX") {
            $mx[] = $record;
        }
    }
    if (count($mx) < 1) {
        return false;
    }
    else {
        return $mx;
    }
}

if (!function_exists("getNumerical")) {
    /**
     * getNumerical()
     *
     * @param string $alpha
     * @return string
     */
    function getNumerical($alpha) {
        $parts = explode("", $alpha);
        foreach($parts as $key => $value)
            if (!is_numeric($value))
                unset($parts[$key]);
        return implode("", $parts);
    }
}

if (!function_exists("getAuthKey")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getAuthKey($username, $password, $format = 'json')
    {
        $return = array();
        $sql = "SELECT `uid`, `email`, `last_login` FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uname` LIKE '$username' AND (`pass` LIKE '$password' OR `pass` LIKE MD5('$password'))";
        list($uid, $email, $last_login) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($uid != $last_login && $uid <> 0)
        {
            $time = time();
            if ($last_login < $time - 3600) {
                $GLOBALS['APIDB']->queryF("UPDATE `" . $GLOBALS['APIDB']->prefix('users') . "` SET `last_login` = '$time', `hits` = `hits` + 1, `actkey` = '" . substr(md5(mt_rand(-time(), time())), 32 - ($len = mt_rand(3,6)), $len) . "' WHERE `uid` = '$uid'");
                $last_login = $time;
            }
            $sql = "SELECT md5(concat(`uid`, `uname`, `email`, `last_login`, `actkey`)) FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '$uid'";
            list($authkey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
            $_SESSION['authkey'] = $authkey;
            setcookie('authkey', $_SESSION['authkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 201, 'authkey' => $_SESSION['authkey'], 'errors' => array());
        } else {
            $_SESSION['authkey'] = md5(NULL);
            setcookie('authkey', $_SESSION['authkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'authkey' => $_SESSION['authkey'] = md5(NULL), 'errors' => array('101' => 'Username and/Or Password Mismatch'));
        }
        return $return;
    }
}


if (!function_exists("getDomainID")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getDomainID($domainkey = '')
    {
        $sql = "SELECT `id` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE '$domainkey' LIKE md5(concat(`id`, '".API_URL."', 'domain'))";
        list($id) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($id <> 0)
        {
            return $id;
        } else {
            $_SESSION['domainkey'] = md5(NULL.'domain');
            setcookie('domainkey', $_SESSION['domainkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'errors' => array('102' => 'Domain Key is not valid!'));
        }
        return $return;
    }
}


if (!function_exists("getEmailID")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getEmailID($emailkey = '')
    {
        $sql = "SELECT `id` FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE '$emailkey' LIKE md5(concat(`id`, '".API_URL."', 'email'))";
        list($id) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($id <> 0)
        {
            return $id;
        } else {
            $_SESSION['emailkey'] = md5(NULL.'email');
            setcookie('emailkey', $_SESSION['emailkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'errors' => array('102' => 'Email Key is not valid!'));
        }
        return $return;
    }
}


if (!function_exists("getAliasID")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getAliasID($aliaskey = '')
    {
        $sql = "SELECT `id` FROM `mail_virtual` WHERE '$aliaskey' LIKE md5(concat(`id`, '".API_URL."', 'alias'))";
        list($id) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($id <> 0)
        {
            return $id;
        } else {
            $_SESSION['aliaskey'] = md5(NULL.'alias');
            setcookie('aliaskey', $_SESSION['aliaskey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'errors' => array('102' => 'Alias Key is not valid!'));
        }
        return $return;
    }
}

if (!function_exists("getUserID")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getUserID($userkey = '')
    {
        $sql = "SELECT `uid` FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE '$userkey' LIKE md5(concat(`uid`, '".API_URL."', 'user'))";
        list($uid) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($uid <> 0)
        {
            return $uid;
        } else {
            $_SESSION['userkey'] = md5(NULL.'user');
            setcookie('userkey', $_SESSION['userkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'errors' => array('105' => 'User Key is not valid!'));
        }
        return $return;
    }
}

if (!function_exists("checkAuthKey")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function checkAuthKey($authkey = '')
    {
        $sql = "SELECT `uid`, `uname` FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE '$authkey' LIKE md5(concat(`uid`, `uname`, `email`, `last_login`, `actkey`))";
        list($uid, $uname) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
        if ($uid <> 0 && !empty($uname))
        {
            $GLOBALS['account'] = $uname;
            $GLOBALS['uid'] = $uid;
            $time = time();
            $GLOBALS['APIDB']->queryF("UPDATE `" . $GLOBALS['APIDB']->prefix('users') . "` SET `last_online` = '$time', `hits` = `hits` + 1 WHERE `uid` = '$uid'");
            $return = array();
        } else {
            $_SESSION['authkey'] = md5(NULL);
            $GLOBALS['uid'] = 0;
            setcookie('authkey', $_SESSION['authkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
            $return = array('code' => 501, 'errors' => array('102' => 'AuthKey is not valid!'));
        }
        return $return;
    }
}


if (!function_exists("addCallback")) {
    /**
     * addEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function addCallback($uri, $posts = array())
    {
        $sql = "INSERT INTO `callbacks` (`uri`, `post`, `created`) VALUES('" . $GLOBALS['APIDB']->escape($uri) . "', '" . $GLOBALS['APIDB']->escape(json_encode($posts)) . "', UNIX_TIMESTAMP())";
        return $GLOBALS['APIDB']->queryF($sql);
    }
}


if (!function_exists("getBaseDomain")) {
    /**
     * Gets the base domain of a tld with subdomains, that is the root domain header for the network rout
     *
     * @param string $url
     *
     * @return string
     */
    function getBaseDomain($uri = '')
    {
        
        static $fallout, $stratauris, $classes;
        
        if (empty($classes))
        {
            
            $attempts = 0;
            $attempts++;
            $classes = array_keys(json_decode(getURIData(API_STRATA_API_URL ."/v1/strata/json.api", 15, 10), true));
            
        }
        if (empty($fallout))
        {
            $fallout = array_keys(json_decode(getURIData(API_STRATA_API_URL ."/v1/fallout/json.api", 15, 10), true));
        }
        
        // Get Full Hostname
        $uri = strtolower($uri);
        $hostname = parse_url($uri, PHP_URL_HOST);
        if (!filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 || FILTER_FLAG_IPV4) === false)
            return $hostname;
        
        // break up domain, reverse
        $elements = explode('.', $hostname);
        $elements = array_reverse($elements);
        
        // Returns Base Domain
        if (in_array($elements[0], $classes))
            return $elements[1] . '.' . $elements[0];
        elseif (in_array($elements[0], $fallout) && in_array($elements[1], $classes))
            return $elements[2] . '.' . $elements[1] . '.' . $elements[0];
        elseif (in_array($elements[0], $fallout))
            return  $elements[1] . '.' . $elements[0];
        else
            return  $elements[1] . '.' . $elements[0];
    }
}

if (!function_exists("addDomains")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function addDomains($authkey, $domain = '', $parentdomainkey = '', $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (strlen($parentdomainkey)==32) {
                $parentdomainid = getDomainID($parentdomainkey);
                $sql = "SELECT `domain` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '$parentdomainid'";
                list($dname) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                if (strlen($domain) < strlen($dname) || substr($domain, strlen($domain) - strlen($dname), strlen($dname)) != $dname)
                    $domain = $domain . "." . $dname; 
            } else 
                $parentdomainid = 0;
            
            $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE (`domain` LIKE '$domain' AND `parentdomainid` = '$parentdomainid')";
            list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
            if ($count==0)
            {
                $zonekey = '';
                $authkey = json_decode(getURIData($uri = API_ZONES_API_URL . "/v1/authkey.api", 45, 45, array('username' => API_ZONES_API_USER, 'password' => API_ZONES_API_PASS, 'format' => 'json')), true);
                if ($GLOBALS['php-curl'][md5($uri)]['http']['code'] == 201 || $GLOBALS['php-curl'][md5($uri)]['http']['code'] == 200 && isset($authkey['authkey']) && !empty($authkey['authkey'])) {
                    $domains = json_decode(getURIData($uri = API_ZONES_API_URL . "/v1/" . $authkey['authkey'] . "/domains/json.api", 45, 45, array()), true);
                    if ($GLOBALS['php-curl'][md5($uri)]['http']['code'] == 201 || $GLOBALS['php-curl'][md5($uri)]['http']['code'] == 200 ) {
                        $basedomain = getBaseDomain("http://$domain");
                        $zonekey = '';
                        foreach($domains as $zonedomain) {
                            if (empty($zonekey) && $basedomain == $zonedomain['name']) {
                                $zonekey = $zonedomain['domainkey'];
                                continue;
                            }
                        }
                    }
                }
                $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('domains') . "` (`domain`, `zonekey`, `uid`, `ispeer`, `isemaildomain`, `parentdomainid`) VALUES ('$domain', '$zonekey', '" . $GLOBALS['uid'] . "', '0', '1', '$parentdomainid')";
                if ($GLOBALS['APIDB']->queryF($sql))
                {
                    $sql = "SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '".$GLOBALS['APIDB']->getInsertId()."'";
                    list($domainkey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                    $_SESSION['domainkey'] = $domainkey;
                    setcookie('domainkey', $_SESSION['masterkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
                    $return = array('code' => 201, 'domainkey' => $_SESSION['domainkey'], 'errors' => array());
                } else {
                    $return = array('code' => 501, 'domainkey' => md5(NULL. 'domainkey'), 'errors' => array($GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
                }
            } else {
                $return = array('code' => 501, 'domainkey' => md5(NULL. 'domainkey'), 'errors' => array('103' => 'Record Already Exists!!!'));
            }
        }
        return $return;
    }
}


if (!function_exists("addEmail")) {
    /**
     * addEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function addEmail($authkey, $name = '', $username = '', $domainkey = '', $password = '', $verify = '', $bytessize = '', $notify = '', $callback = '', $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (strlen($domainkey)==32) {
                $domainid = getDomainID($domainkey);
                $sql = "SELECT `domain`, `pid`, `zonekey` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '$domainid'";
                list($domain, $pid, $zonekey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                $domainpath = implode(DS, array_reverse(explode('.', $domain)));
            } else
                $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('123' => 'Domain Key not Found!!!'));
                
            if (empty($return) && $password != $verify)
                $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('121' => 'Password does not match verified password field!!!'));
            
            if (empty($return) && $bytessize < (API_MINIMUM_INBOX_SIZES * 1024 * 1024 * 1024))
                $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('124' => 'Size of the mailbox is too small, it is smaller than the minimal size of: ' . API_MINIMUM_INBOX_SIZES . 'Mb\'s!!!'));
            
            if (empty($return) && $bytessize > (API_MAXIMUM_INBOX_SIZES * 1024 * 1024 * 1024))
                $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('125' => 'Size of the mailbox is too large, it is greater than the maximum size of: ' . API_MAXIMUM_INBOX_SIZES . 'Mb\'s!!!'));

            if (empty($return) && !checkEmail($notify))
                $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('126' => 'Notification Email Address is not an addressed formating correctly!!!'));
            
            if (empty($return))
            {
                $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE ((`email` LIKE '$username@$domain' OR `username` = '$username@$domain') AND `domainid` = '$domainid')";
                list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                if ($count==0)
                {
                    $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('mail_users') . "` (`mode`, `name`, `email`, `username`, `notify`, `actkey`, `password`, `password_enc`, `uid`, `pid`, `homedir`, `maildir`, `postfix`, `domainid`, `pop3`, `imap`, `mboxsize`, `mboxonline`, `mboxoffline`, `created`, `callback`) VALUES ('new', '" . $GLOBALS['APIDB']->escape($name) . "', '" . $GLOBALS['APIDB']->escape("$username@$domain") ."', '" . $GLOBALS['APIDB']->escape("$username@$domain") ."', '" . $GLOBALS['APIDB']->escape($notify) . "', '" . substr(sha1(microtime(true)), mt_rand(0, 34), mt_rand(4,6)) . "', DES_ENCRYPT('$password', '" . $GLOBALS['APIDB']->escape("$username@$domain") . "'), ENCRYPT('$password'), '" . $GLOBALS['uid'] . "', '$pid', '" . ($homedir = API_HOMEDIR_PATH . DS . $domainpath . DS . $username) . "', '" . ($maildir = API_MAILDIR_PATH . DS . $domainpath . DS . $username) . "', 'Y', '$domainid', 1, 1, '" . (API_INTIALISE_INBOX_SIZES * 1024 * 1024 * 1024) . "', '$bytessize', '" . (API_OFFLINE_INBOX_SIZES * 1024 * 1024 * 1024) . "', UNIX_TIMESTAMP(), '" . $GLOBALS['APIDB']->escape($callback) . "')"; 
                    if ($GLOBALS['APIDB']->queryF($sql))
                    {
                        $sql = "SELECT md5(concat(`id`, '" . API_URL . "', 'email')) FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE `id` = '".$GLOBALS['APIDB']->getInsertId()."'";
                        list($emailkey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                        $_SESSION['emailkey'] = $emailkey;
                        setcookie('emailkey', $_SESSION['emailkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
                        
                        if (!is_dir($homedir))
                            mkdir($homedir, 0777, true);
                        
                        if (!is_dir($maildir))
                            mkdir($maildir, 0777, true);
                        
                        if (!is_dir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys'))
                            mkdir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys', 0777, true);
                            
                        if (file_exists($script = dirname(__DIR__) . DS . 'crons' . DS . 'generate-pgpkeys.sh'))
                            $sh = file($script);
                        else {
                            $sh = array();
                            $sh[] = "unlink \"" . dirname(__DIR__) . DS . 'crons' . DS . 'generate-pgpkeys.sh' . "\"\n";
                        }
                        
                        writeRawFile($diz = API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . "$username@$domain.diz", str_replace('%name', $name, str_replace('%email', "$username@$domain", str_replace('%subbits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), str_replace('%bits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), file_get_contents(__DIR__ . DS . 'data' . DS . 'gen-key-script.diz'))))));
                        $sh[] = "gpg --batch --gen-key \"$diz\"\n";
                        $sh[] = "unlink \"$diz\"\n";
                        $sh[] = "gpg --armor --export $username@$domain > \"" . API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $username . '@' . $domain . ".asc\"\n";
                        foreach(file(__DIR__ . DS . 'data' . DS . 'keyservers-hostnames.diz') as $keyserver)
                            $sh[] = "gpg --keyserver " . str_replace(array("\n", "\r", "\t"), "", trim($keyserver)) . " --send-key $username@$domain\n"; 
                        writeRawFile($script, implode("", $sh));
                        
                        if (strlen($callback) > 0)
                            addCallback($callback, array('op' => 'created-email', 'emailkey' => $emailkey, 'email' => '$username@$domain', 'username' => $username, 'domain' => $domain, 'domainkey' => $domainkey));
                        
                        $return = array('code' => 201, 'emailkey' => $_SESSION['emailkey'], 'errors' => array());
                    } else {
                        $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('sql' => $sql, $GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
                    }
                } else {
                    $return = array('code' => 501, 'emailkey' => md5(NULL. 'email'), 'errors' => array('103' => 'Record Already Exists!!!'));
                }
            }
        }
        return $return;
    }
}


if (!function_exists("addAlias")) {
    /**
     * addEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function addAlias($authkey, $name = '', $username = '', $domainkey = '', $destination = '', $callback = '', $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (strlen($domainkey)==32) {
                $domainid = getDomainID($domainkey);
                $sql = "SELECT `domain`, `pid`, `zonekey` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `id` = '$domainid'";
                list($domain, $pid, $zonekey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                $domainpath = implode(DS, array_reverse(explode('.', $domain)));
            } else
                $return = array('code' => 501, 'aliaskey' => md5(NULL. 'alias'), 'errors' => array('123' => 'Domain Key not Found!!!'));
                
            if (empty($return) && !checkEmail($destination))
                $return = array('code' => 501, 'aliaskey' => md5(NULL. 'alias'), 'errors' => array('126' => 'Destination Email Address is not an addressed formating correctly!!!'));

            if (empty($return))
            {
                $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE ((`email` LIKE '" . $GLOBALS['APIDB']->escape("$username@$domain") . "' OR `email_full` = '$username@$domain') AND `destination` = '" . $GLOBALS['APIDB']->escape($destination) . "')";
                list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                if ($count==0)
                {
                    $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` (`name`, `email`, `email_full`, `destination`, `domainid`, `uid`, `pid`, `created`, `callback`) VALUES ('" . $GLOBALS['APIDB']->escape($name) . "', '" . $GLOBALS['APIDB']->escape("$username@$domain") . "', '" . $GLOBALS['APIDB']->escape("$username@$domain") . "', '" . $GLOBALS['APIDB']->escape($destination) . "', '$domainid', '" . $GLOBALS['uid'] . "', '$pid', UNIX_TIMESTAMP(), '" . $GLOBALS['APIDB']->escape($callback) . "')";
                    if ($GLOBALS['APIDB']->queryF($sql))
                    {
                        $sql = "SELECT md5(concat(`id`, '" . API_URL . "', 'alias')) FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `id` = '".$GLOBALS['APIDB']->getInsertId()."'";
                        list($aliaskey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                        $_SESSION['aliaskey'] = $aliaskey;
                        setcookie('aliaskey', $_SESSION['aliaskey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
                        
                        if (!is_dir($maildir))
                            mkdir($maildir, 0777, true);
                                            
                        if (!is_dir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys'))
                            mkdir(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys', 0777, true);
                                            
                        if (!file_exists(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $username . '@' . $domain . ".asc")) {
                            if (file_exists($script = dirname(__DIR__) . DS . 'crons' . DS . 'generate-pgpkeys.sh'))
                                $sh = file($script);
                            else {
                                $sh = array();
                                $sh[] = "unlink \"" . dirname(__DIR__) . DS . 'crons' . DS . 'generate-pgpkeys.sh' . "\"\n";
                            }
                            
                            writeRawFile($diz = API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . "$username@$domain.diz", str_replace('%name', "$username@$domain", str_replace('%email', "$username@$domain", str_replace('%subbits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), str_replace('%bits', mt_rand(API_MINBITS_PGP_KEYS, API_MAXBITS_PGP_KEYS), file_get_contents(__DIR__ . DS . 'data' . DS . 'gen-key-script.diz'))))));
                            $sh[] = "gpg --batch --gen-key \"$diz\"\n";
                            $sh[] = "unlink \"$diz\"\n";
                            $sh[] = "gpg --armor --export $username@$domain > \"" . API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $username . '@' . $domain . ".asc\"\n";
                            foreach(file(__DIR__ . DS . 'data' . DS . 'keyservers-hostnames.diz') as $keyserver)
                                $sh[] = "gpg --keyserver " . str_replace(array("\n", "\r", "\t"), "", trim($keyserver)) . " --send-key $username@$domain\n";
                            writeRawFile($script, implode("", $sh));
                                
                            if (strlen($callback) > 0)
                                addCallback($callback, array('op' => 'created-alias', 'aliaskey' => $aliaskey, 'alias' => '$username@$domain', 'username' => $username, 'domain' => $domain, 'domainkey' => $domainkey, 'destination' => $destination));
                                                                
                            $return = array('code' => 201, 'aliaskey' => $_SESSION['aliaskey'], 'errors' => array());
                        } else {
                            
                            if (strlen($callback) > 0)
                                addCallback($callback, array('op' => 'created-alias', 'aliaskey' => $aliaskey, 'alias' => '$username@$domain', 'username' => $username, 'domain' => $domain, 'domainkey' => $domainkey, 'destination' => $destination, 'pgpkey' => file_get_contents(API_MAILDIR_PATH . DS . $domainpath . DS . '.pgp-keys' . DS . $username . '@' . $domain . ".asc")));
                                
                            $return = array('code' => 201, 'aliaskey' => $_SESSION['aliaskey'], 'errors' => array());
                        }
                    } else {
                        $return = array('code' => 501, 'aliaskey' => md5(NULL. 'email'), 'errors' => array('sql' => $sql, $GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
                    }
                } else {
                    $return = array('code' => 501, 'aliaskey' => md5(NULL. 'email'), 'errors' => array('103' => 'Record Already Exists!!!'));
                }
            }
        }
        return $return;
    }
}


if (!function_exists("addUser")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function addUser($authkey, $uname, $email = '', $pass = '', $vpass = '', $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (!checkEmail($email))
                return array('code' => 501, 'errors' => array('109' => 'e-Mail format isn\'t valid!!!'));
                
                if (!empty($pass) && !empty($vpass) && $pass != $vpass)
                    return array('code' => 501, 'errors' => array('108' => 'Password & verify password do not match!!!'));
                    
                    $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE (`uname` LIKE '" .$GLOBALS['APIDB']->escape($uname). "') OR (`uname` LIKE '" .$GLOBALS['APIDB']->escape($uname). "' AND `email` LIKE '" .$GLOBALS['APIDB']->escape($email). "')";
                    list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                    if ($count==0)
                    {
                        $sql = "INSERT INTO `" . $GLOBALS['APIDB']->prefix('users') . "` (`uname`, `email`, `pass`) VALUES ('" .$GLOBALS['APIDB']->escape($uname). "', '" .$GLOBALS['APIDB']->escape($email). "', md5('" .$GLOBALS['APIDB']->escape($pass). "'))";
                        if ($GLOBALS['APIDB']->queryF($sql))
                        {
                            $sql = "SELECT md5(concat(`uid`, '" . API_URL . "', 'user')) FROM `" . $GLOBALS['APIDB']->prefix('users') . "` WHERE `uid` = '".$GLOBALS['APIDB']->getInsertId()."'";
                            list($userkey) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
                            $_SESSION['userkey'] = $userkey;
                            setcookie('userkey', $_SESSION['userkey'], 3600 + $time, '/', API_COOKIE_DOMAIN);
                            $return = array('code' => 201, 'userkey' => $_SESSION['userkey'], 'errors' => array());
                            
                            /*require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'apimailer.php';
                            
                            $mail = new APIMailer(API_LICENSE_EMAIL, API_LICENSE_COMPANY);
                            $body = file_get_contents(__DIR__  . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'new_user_emailtemplate.html' );
                            $body = str_replace('%apilogo', API_URL . '/assets/images/logo_350x350.png', $body);
                            $body = str_replace('%apiurl', API_URL, $body);
                            $body = str_replace('%companyname', API_LICENSE_COMPANY, $body);
                            $body = str_replace('%account', $GLOBALS['account'], $body);
                            $body = str_replace('%uname', $uname, $body);
                            $body = str_replace('%pass', $pass, $body);
                            $body = str_replace('%email', $email, $body);
                            $mail->sendMail($email, array(), array(), "Zone API Creditials as established by: " . $GLOBALS['account'], $body, array(), "", true);
                            */
                        } else {
                            $return = array('code' => 501, 'userkey' => md5(NULL. 'user'), 'errors' => array($GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
                        }
                    } else {
                        $return = array('code' => 501, 'userkey' => md5(NULL. 'user'), 'errors' => array('107' => 'User Record Already Exists!!!'));
                    }
        }
        return $return;
    }
}



if (!function_exists("editRecord")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function editRecord($table, $authkey, $id, $vars = array(), $fields = array(), $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (!empty($id) && is_array($id))
                return $id;
                
            foreach($vars as $key => $value)
                if (!in_array($key, $fields))
                    unset($vars[$key]);
            
            foreach($fields as $key => $value)
                if (!in_array($key, $vars))
                    unset($fields[$key]);
                
            if (count($vars) == 0)
                return array('code' => 501, 'errors' => array('110' => 'No records fields specified for edit this supports: '.implode(', ', $fields).'!!!'));

            switch ($table)
            {
                case 'users':
                    if (isset($vars['email']) && !empty($vars['email']) && !checkEmail($vars['email']))
                        return array('code' => 501, 'errors' => array('109' => 'e-Mail format isn\'t valid!!!'));
                        if (!empty($vars['pass']) && !empty($vars['vpass']) && $vars['pass'] != $vars['vpass'])
                            return array('code' => 501, 'errors' => array('108' => 'Password & verify password do not match!!!'));
                            elseif (!empty($vars['pass']) && !empty($vars['vpass']) && $vars['pass'] == $vars['vpass']) {
                                $vars['pass'] = md5($vars['pass']);
                                unset($vars['vpass']);
                            } else {
                                unset($vars['pass']);
                                unset($vars['vpass']);
                            }
                            $old = $GLOBALS["APIDB"]->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE `uid` = '$id'"));
                            $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE (`uname` LIKE '" .$GLOBALS['APIDB']->escape($vars['uname']). "') OR (`email` LIKE '" .$GLOBALS['APIDB']->escape($vars['email']). "'))";
                            break;
                case 'email':
                    $old = $GLOBALS["APIDB"]->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE `id` = '$id'"));
                    $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE (`name` LIKE '" .$GLOBALS['APIDB']->escape($vars['name']). "' AND `content` LIKE '" .$GLOBALS['APIDB']->escape($vars['content']). "' AND `type` LIKE '" . $old['type'] . "'))";
                    break;
                case 'domain':
                    $old = $GLOBALS["APIDB"]->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE `id` = '$id'"));
                    $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE (`name` LIKE '" .$GLOBALS['APIDB']->escape($vars['name']). "' AND `type` LIKE '" . $vars['type'] . "') OR (`master` LIKE '" .$GLOBALS['APIDB']->escape($vars['master']). "' AND `type` LIKE '" . $vars['type'] . "'))";
                    break;
                case 'alias':
                    $old = $GLOBALS["APIDB"]->fetchArray($GLOBALS['APIDB']->queryF("SELECT * FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE `id` = '$id'"));
                    $sql = "SELECT COUNT(*) FROM `" . $GLOBALS['APIDB']->prefix($table) . "` WHERE (`ip` LIKE '" .$GLOBALS['APIDB']->escape($vars['ip']). "' AND `nameserver` LIKE '" .$GLOBALS['APIDB']->escape($vars['nameserver']). "'))";
                    break;
            }
            list($count) = $GLOBALS['APIDB']->fetchRow($GLOBALS['APIDB']->queryF($sql));
            if ($count==0)
            {
                $sql = "UPDATE `" . $GLOBALS['APIDB']->prefix($table) . "` SET ";
                $u=0;
                foreach($vars as $key => $value)
                {
                    $u++;
                    
                    if ($key = 'password') {
                        $sql .= "`$key` = DES_ENCRYPT('" . $GLOBALS['APIDB']->escape($value) . ($u < count($vars) + 1?"', `email`), ":"', `email`) ");
                        $sql .= "`$key_enc` = ENCRYPT('" . $GLOBALS['APIDB']->escape($value) . ($u < count($vars)?"'), ":"') ");
                    } else 
                        $sql .= "`$key` = '" . $GLOBALS['APIDB']->escape($value) . ($u < count($vars)?"', ":"' ");
                }
                switch ($table)
                {
                    case 'users':
                        $sql .= "WHERE `uid` = '$id'";
                        break;
                    default:
                        $sql .= "WHERE `id` = '$id'";
                        break;
                }
                if ($GLOBALS['APIDB']->queryF($sql))
                {
                    $return = array('code' => 201, 'affected' =>$GLOBALS['APIDB']->getAffectedRows(), 'errors' => array());
                } else {
                    $return = array('code' => 501, 'errors' => array($GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
                }
            } else {
                $return = array('code' => 501, 'errors' => array('107' => 'User Record Already Exists!!!'));
            }
        }
        return $return;
    }
}
if (!function_exists("extractPGPKeys")) {
    
    function extractPGPKeys($data) 
    {
        $data = NULL.$data;
        $certs = array();
        $fpos = 0;
        if (strpos($data, API_HEADER_PGP_KEYS))
        {
            while($ipos = strpos($data, API_HEADER_PGP_KEYS, $fpos))
            {
                $fpos = strpos($data, API_FOOTER_PGP_KEYS, $ipos);
                if ($fpos != 0 && $ipos != 0)
                    $certs[] = substr($data, $ipos, $fpos - $ipos + strlen(API_FOOTER_PGP_KEYS));
            }
        }
        if (count($certs))
            return $certs;
        return false;
    }
}

if (!function_exists("deleteRecord")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function deleteRecord($table, $authkey, $id)
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            if (!empty($id) && is_array($id))
                return $id;
                
            $sql = "DELETE FROM `" . $GLOBALS['APIDB']->prefix($table) . "` ";
            switch ($table)
            {
                case 'users':
                    $sql .= "WHERE `uid` = '$id'";
                    break;
                default:
                    $sql .= "WHERE `id` = '$id'";
                    break;
            }
            if ($GLOBALS['APIDB']->queryF($sql))
            {
                $return = array('code' => 201, 'affected' =>$GLOBALS['APIDB']->getAffectedRows(), 'errors' => array());
            } else {
                $return = array('code' => 501, 'errors' => array($GLOBALS['APIDB']->errno() => $GLOBALS['APIDB']->error()));
            }
        } else {
            $return = array('code' => 501, 'errors' => array('107' => 'User Record Already Exists!!!'));
        }
        return $return;
    }
}

if (!function_exists("getDomains")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getDomains($authkey, $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            $return['code'] = 201;
            $sql = "SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) as `domainkey`, `name`, `zonekey`, md5(concat(`parentdomainid`, '" . API_URL . "', 'domain')) as `parentdomainkey` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` ORDER BY `name` ASC, `master` ASC, `type` DESC";
            $result = $GLOBALS['APIDB']->queryF($sql);
            while($domain = $GLOBALS['APIDB']->fetchArray($result))
                $return['domains'][] = $domain;
        }
        return $return;
    }
}


if (!function_exists("getUsers")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getUsers($authkey, $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            $return['code'] = 201;
            $sql = "SELECT md5(concat(`uid`, '" . API_URL . "', 'user')) as `userkey`, `uname`, `email`, `hits`, `last_online`, `last_login` FROM `" . $GLOBALS['APIDB']->prefix('users') . "` ORDER BY `uname` ASC, `email` ASC, `hits` DESC";
            $result = $GLOBALS['APIDB']->queryF($sql);
            while($user = $GLOBALS['APIDB']->fetchArray($result))
                $return['users'][] = $user;
        }
        return $return;
    }
}


if (!function_exists("getEmail")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getEmail($authkey, $emailkey = '', $format = 'json')
    {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            $return['code'] = 201;
            $sql = "SELECT * FROM `" . $GLOBALS['APIDB']->prefix('mail_users') . "` WHERE `id` = " . getEmailID($emailkey);
            $result = $GLOBALS['APIDB']->queryF($sql);
            while($email = $GLOBALS['APIDB']->fetchArray($result)) {
                unset($email['id']);
                unset($email['password']);
                unset($email['password_enc']);
                $return[$emailkey][] = $email;
            }
        }
        return $return;
    }
}



if (!function_exists("getAlias")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function getAlias($authkey, $aliaskey = '', $format = 'json')  {
        $return = checkAuthKey($authkey);
        if (empty($return))
        {
            $return['code'] = 201;
            $sql = "SELECT * FROM `" . $GLOBALS['APIDB']->prefix('mail_virtual') . "` WHERE `id` = " . getAliasID($aliaskey);
            $result = $GLOBALS['APIDB']->queryF($sql);
            while($alias = $GLOBALS['APIDB']->fetchArray($result)) {
                unset($alias['id']);
                $return[$aliaskey][] = $alias;
            }
        }
        return $return;
    }
}


if (!function_exists("checkEmail")) {
    /**
     * checkEmail()
     *
     * @param mixed $email
     * @param mixed $antispam
     * @return bool|mixed
     */
    function checkEmail($email, $antispam = false)
    {
        if (!$email || !preg_match('/^[^@]{1,64}@[^@]{1,255}$/', $email)) {
            return false;
        }
        $email_array      = explode('@', $email);
        $local_array      = explode('.', $email_array[0]);
        $local_arrayCount = count($local_array);
        for ($i = 0; $i < $local_arrayCount; ++$i) {
            if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/\=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/\=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
                return false;
            }
        }
        if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) {
            $domain_array = explode('.', $email_array[1]);
            if (count($domain_array) < 2) {
                return false; // Not enough parts to domain
            }
            for ($i = 0; $i < count($domain_array); ++$i) {
                if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
                    return false;
                }
            }
        }
        if ($antispam) {
            $email = str_replace('@', ' at ', $email);
            $email = str_replace('.', ' dot ', $email);
        }
        
        return $email;
    }
}

if (!function_exists("writeRawFile")) {
    /**
     *
     * @param string $file
     * @param string $data
     */
    function writeRawFile($file = '', $data = '')
    {
        $lineBreak = "\n";
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $lineBreak = "\r\n";
        }
        if (!is_dir(dirname($file)))
            mkdir(dirname($file), 0777, true);
            if (is_file($file))
                unlink($file);
                $data = str_replace("\n", $lineBreak, $data);
                $ff = fopen($file, 'w');
                fwrite($ff, $data, strlen($data));
                fclose($ff);
    }
}

if (!function_exists("getCompleteFilesListAsArray")) {
	function getCompleteFilesListAsArray($dirname, $result = array())
	{
		foreach(getCompleteDirListAsArray($dirname) as $path)
			foreach(getFileListAsArray($path) as $file)
				$result[$path.DIRECTORY_SEPARATOR.$file] = $path.DIRECTORY_SEPARATOR.$file;
				return $result;
	}

}


if (!function_exists("getCompleteDirListAsArray")) {
	function getCompleteDirListAsArray($dirname, $result = array())
	{
		$result[$dirname] = $dirname;
		foreach(getDirListAsArray($dirname) as $path)
		{
			$result[$dirname . DIRECTORY_SEPARATOR . $path] = $dirname . DIRECTORY_SEPARATOR . $path;
			$result = getCompleteDirListAsArray($dirname . DIRECTORY_SEPARATOR . $path, $result);
		}
		return $result;
	}

}

if (!function_exists("getCompleteHistoryListAsArray")) {
	function getCompleteHistoryListAsArray($dirname, $result = array())
	{
		foreach(getCompleteDirListAsArray($dirname) as $path)
		{
			foreach(getHistoryListAsArray($path) as $file=>$values)
				$result[$path][sha1_file($path . DIRECTORY_SEPARATOR . $values['file'])] = array_merge(array('fullpath'=>$path . DIRECTORY_SEPARATOR . $values['file']), $values);
		}
		return $result;
	}
}

if (!function_exists("getDirListAsArray")) {
	function getDirListAsArray($dirname)
	{
		$ignored = array(
				'cvs' ,
				'_darcs');
		$list = array();
		if (substr($dirname, - 1) != '/') {
			$dirname .= '/';
		}
		if ($handle = opendir($dirname)) {
			while ($file = readdir($handle)) {
				if (substr($file, 0, 1) == '.' || in_array(strtolower($file), $ignored))
					continue;
					if (is_dir($dirname . $file)) {
						$list[$file] = $file;
					}
			}
			closedir($handle);
			asort($list);
			reset($list);
		}

		return $list;
	}
}

if (!function_exists("getFileListAsArray")) {
	function getFileListAsArray($dirname, $prefix = '')
	{
		$filelist = array();
		if (substr($dirname, - 1) == '/') {
			$dirname = substr($dirname, 0, - 1);
		}
		if (is_dir($dirname) && $handle = opendir($dirname)) {
			while (false !== ($file = readdir($handle))) {
				if (! preg_match('/^[\.]{1,2}$/', $file) && is_file($dirname . '/' . $file)) {
					$file = $prefix . $file;
					$filelist[$file] = $file;
				}
			}
			closedir($handle);
			asort($filelist);
			reset($filelist);
		}

		return $filelist;
	}
}

if (!function_exists("getHistoryListAsArray")) {
	function getHistoryListAsArray($dirname, $prefix = '')
	{
		$formats = cleanWhitespaces(file(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'history-formats.diz'));
		$filelist = array();

		if ($handle = opendir($dirname)) {
			while (false !== ($file = readdir($handle))) {
				foreach($formats as $format)
					if (substr(strtolower($file), strlen($file)-strlen(".".$format)) == strtolower(".".$format)) {
						$file = $prefix . $file;
						$filelist[$file] = array('file'=>$file, 'type'=>$format, 'sha1' => sha1_file($dirname . DIRECTORY_SEPARATOR . $file));
					}
			}
			closedir($handle);
		}
		return $filelist;
	}
}


if (!function_exists("cleanWhitespaces")) {
	/**
	 *
	 * @param array $array
	 */
	function cleanWhitespaces($array = array())
	{
		foreach($array as $key => $value)
		{
			if (is_array($value))
				$array[$key] = cleanWhitespaces($value);
				else {
					$array[$key] = trim(str_replace(array("\n", "\r", "\t"), "", $value));
				}
		}
		return $array;
	}
}


if (!function_exists("whitelistGetIP")) {

	/* function whitelistGetIPAddy()
	 * 
	 * 	provides an associative array of whitelisted IP Addresses
	 * @author 		Simon Roberts (Chronolabs) simon@labs.coop
	 * 
	 * @return 		array
	 */
	function whitelistGetIPAddy() {
		return array_merge(whitelistGetNetBIOSIP(), file(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'whitelist.txt'));
	}
}

if (!function_exists("whitelistGetNetBIOSIP")) {

	/* function whitelistGetNetBIOSIP()
	 *
	 * 	provides an associative array of whitelisted IP Addresses base on TLD and NetBIOS Addresses
	 * @author 		Simon Roberts (Chronolabs) simon@labs.coop
	 *
	 * @return 		array
	 */
	function whitelistGetNetBIOSIP() {
		$ret = array();
		foreach(file(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'whitelist-domains.txt') as $domain) {
			$ip = gethostbyname($domain);
			$ret[$ip] = $ip;
		} 
		return $ret;
	}
}

if (!function_exists("whitelistGetIP")) {

	/* function whitelistGetIP()
	 *
	 * 	get the True IPv4/IPv6 address of the client using the API
	 * @author 		Simon Roberts (Chronolabs) simon@labs.coop
	 * 
	 * @param		boolean		$asString	Whether to return an address or network long integer
	 * 
	 * @return 		mixed
	 */
	function whitelistGetIP($asString = true){
		// Gets the proxy ip sent by the user
		$proxy_ip = '';
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$proxy_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else
		if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
			$proxy_ip = $_SERVER['HTTP_X_FORWARDED'];
		} else
		if (! empty($_SERVER['HTTP_FORWARDED_FOR'])) {
			$proxy_ip = $_SERVER['HTTP_FORWARDED_FOR'];
		} else
		if (!empty($_SERVER['HTTP_FORWARDED'])) {
			$proxy_ip = $_SERVER['HTTP_FORWARDED'];
		} else
		if (!empty($_SERVER['HTTP_VIA'])) {
			$proxy_ip = $_SERVER['HTTP_VIA'];
		} else
		if (!empty($_SERVER['HTTP_X_COMING_FROM'])) {
			$proxy_ip = $_SERVER['HTTP_X_COMING_FROM'];
		} else
		if (!empty($_SERVER['HTTP_COMING_FROM'])) {
			$proxy_ip = $_SERVER['HTTP_COMING_FROM'];
		}
		if (!empty($proxy_ip) && $is_ip = preg_match('/^([0-9]{1,3}.){3,3}[0-9]{1,3}/', $proxy_ip, $regs) && count($regs) > 0)  {
			$the_IP = $regs[0];
		} else {
			$the_IP = $_SERVER['REMOTE_ADDR'];
		}
			
		$the_IP = ($asString) ? $the_IP : ip2long($the_IP);
		return $the_IP;
	}
}


if (!function_exists("getURIData")) {
    
    /* function yonkURIData()
     *
     * 	Get a supporting domain system for the API
     * @author 		Simon Roberts (Chronolabs) simon@labs.coop
     *
     * @return 		float()
     */
    function getURIData($uri = '', $timeout = 25, $connectout = 25, $post = array(), $headers = array())
    {
        if (!function_exists("curl_init"))
        {
            die("Install PHP Curl Extension ie: $ sudo apt-get install php-curl -y");
        }
        $GLOBALS['php-curl'][md5($uri)] = array();
        if (!$btt = curl_init($uri)) {
            return false;
        }
        if (count($post)==0 || empty($post))
            curl_setopt($btt, CURLOPT_POST, false);
        else {
            $uploadfile = false;
            foreach($post as $field => $value)
                if (substr($value , 0, 1) == '@' && !file_exists(substr($value , 1, strlen($value) - 1)))
                    unset($post[$field]);
                else
                    $uploadfile = true;
            curl_setopt($btt, CURLOPT_POST, true);
            curl_setopt($btt, CURLOPT_POSTFIELDS, http_build_query($post));
            
            if (!empty($headers))
                foreach($headers as $key => $value)
                    if ($uploadfile==true && substr($value, 0, strlen('Content-Type:')) == 'Content-Type:')
                        unset($headers[$key]);
            if ($uploadfile==true)
                $headers[]  = 'Content-Type: multipart/form-data';
        }
        if (count($headers)==0 || empty($headers)) {
            curl_setopt($btt, CURLOPT_HEADER, false);
            curl_setopt($btt, CURLOPT_HTTPHEADER, array());
        } else {
            curl_setopt($btt, CURLOPT_HEADER, false);
            curl_setopt($btt, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($btt, CURLOPT_CONNECTTIMEOUT, $connectout);
        curl_setopt($btt, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($btt, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($btt, CURLOPT_VERBOSE, false);
        curl_setopt($btt, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($btt, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($btt);
        $GLOBALS['php-curl'][md5($uri)]['http']['uri'] = $uri;
        $GLOBALS['php-curl'][md5($uri)]['http']['posts'] = $post;
        $GLOBALS['php-curl'][md5($uri)]['http']['headers'] = $headers;
        $GLOBALS['php-curl'][md5($uri)]['http']['code'] = curl_getinfo($btt, CURLINFO_HTTP_CODE);
        $GLOBALS['php-curl'][md5($uri)]['header']['size'] = curl_getinfo($btt, CURLINFO_HEADER_SIZE);
        $GLOBALS['php-curl'][md5($uri)]['header']['value'] = curl_getinfo($btt, CURLINFO_HEADER_OUT);
        $GLOBALS['php-curl'][md5($uri)]['size']['download'] = curl_getinfo($btt, CURLINFO_SIZE_DOWNLOAD);
        $GLOBALS['php-curl'][md5($uri)]['size']['upload'] = curl_getinfo($btt, CURLINFO_SIZE_UPLOAD);
        $GLOBALS['php-curl'][md5($uri)]['content']['length']['download'] = curl_getinfo($btt, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $GLOBALS['php-curl'][md5($uri)]['content']['length']['upload'] = curl_getinfo($btt, CURLINFO_CONTENT_LENGTH_UPLOAD);
        $GLOBALS['php-curl'][md5($uri)]['content']['type'] = curl_getinfo($btt, CURLINFO_CONTENT_TYPE);
        curl_close($btt);
        return $data;
    }
}

if (!class_exists("XmlDomConstruct")) {
	/**
	 * class XmlDomConstruct
	 * 
	 * 	Extends the DOMDocument to implement personal (utility) methods.
	 *
	 * @author 		Simon Roberts (Chronolabs) simon@labs.coop
	 */
	class XmlDomConstruct extends DOMDocument {
	
		/**
		 * Constructs elements and texts from an array or string.
		 * The array can contain an element's name in the index part
		 * and an element's text in the value part.
		 *
		 * It can also creates an xml with the same element tagName on the same
		 * level.
		 *
		 * ex:
		 * <nodes>
		 *   <node>text</node>
		 *   <node>
		 *     <field>hello</field>
		 *     <field>world</field>
		 *   </node>
		 * </nodes>
		 *
		 * Array should then look like:
		 *
		 * Array (
		 *   "nodes" => Array (
		 *     "node" => Array (
		 *       0 => "text"
		 *       1 => Array (
		 *         "field" => Array (
		 *           0 => "hello"
		 *           1 => "world"
		 *         )
		 *       )
		 *     )
		 *   )
		 * )
		 *
		 * @param mixed $mixed An array or string.
		 *
		 * @param DOMElement[optional] $domElement Then element
		 * from where the array will be construct to.
		 * 
		 * @author 		Simon Roberts (Chronolabs) simon@labs.coop
		 *
		 */
		public function fromMixed($mixed, DOMElement $domElement = null) {
	
			$domElement = is_null($domElement) ? $this : $domElement;
	
			if (is_array($mixed)) {
				foreach( $mixed as $index => $mixedElement ) {
	
					if ( is_int($index) ) {
						if ( $index == 0 ) {
							$node = $domElement;
						} else {
							$node = $this->createElement($domElement->tagName);
							$domElement->parentNode->appendChild($node);
						}
					}
					 
					else {
						$node = $this->createElement($index);
						$domElement->appendChild($node);
					}
					 
					$this->fromMixed($mixedElement, $node);
					 
				}
			} else {
				$domElement->appendChild($this->createTextNode($mixed));
			}
			 
		}
		 
	}
}


function getHTMLForm($mode = '', $authkey = '')
{
    if (empty($authkey) && isset($_COOKIE['authkey']))
        $authkey = $_COOKIE['authkey'];
    elseif (empty($authkey) && isset($_SESSION['authkey']))
        $authkey = $_SESSION['authkey'];
    elseif (empty($authkey))
        $authkey = md5(NULL);
        
    $form = array();
    switch ($mode)
    {
        case "authkey":
            $form[] = "<form name='auth-key' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/authkey.api">';
            $form[] = "\t<table class='auth-key' id='auth-key' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='username'>Username:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='username' id='username' size='41' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='password'>Password:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='password' name='password' id='password' size='41' /><br/>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='format'>Output Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='authkey' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Get URL Auth-key' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "changepass":
            $form[] = "<form name='change-pass' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . $_SERVER["REQUEST_URI"] . '">';
            $form[] = "\t<table class='change-pass' id='change-pass' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='pgpkey'>New Password:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='password' name='pass' id='pass' size='32' />";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='vpass'>Verify Password:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='password' name='vpass' id='vpass' size='32' />";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='changepass' name='op'>";
            $form[] = "\t\t\t\t<input type='submit' value='Change Password' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "changenotify":
            $form[] = "<form name='change-notify' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . $_SERVER["REQUEST_URI"] . '">';
            $form[] = "\t<table class='change-notify' id='change-notify' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='pgpkey'>New Notify Email:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='text' name='notify' id='notify' size='41' />";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='changenotify' name='op'>";
            $form[] = "\t\t\t\t<input type='submit' value='Change Notify Email' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "addpgpkey":
            $form[] = "<form name='add-pgp-key' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/pgpkey.api">';
            $form[] = "\t<table class='add-pgp-key' id='auth-key' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='pgpkey'>PGP Key:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<textarea name='pgpkey' id='pgpkey' cols='42' rows='17'></textarea>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='pgpkey'>Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='pgpkey' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Add pgp key' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "newdomain":
            $form[] = "<form name='new-domain' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/' . $authkey . '/domains.api">';
            $form[] = "\t<table class='new-domain' id='auth-domain' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='domain'>Domain Name:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='domain' id='domain' size='41' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='domain'>Domain:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='parent' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='' selected='selected'>(No Parent Domain)</option>";
            $result = $GLOBALS['APIDB']->queryF("SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) as `key`, `domain` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` ORDER BY `domain` ASC");
            while($row = $GLOBALS['APIDB']->fetchArray($result))
                $form[] = "\t\t\t\t\t<option value='".$row['key']."'>".$row['domain']."</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='format'>Output Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='domains' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Create New Domain' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "newalias":
            $form[] = "<form name='new-alias' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/' . $authkey . '/aliases.api">';
            $form[] = "\t<table class='new-alias' id='alias-record' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='notify'>Email Alias Name:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='name' id='name' size='41' maxlen='255' value='' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='email'>Email:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<input type='textbox' name='username' id='username' size='23' />&nbsp;<strong style='font-size: 247%'>@</strong>&nbsp;";
            $form[] = "\t\t\t\t<select name='domain' id='format'/>";
            $result = $GLOBALS['APIDB']->queryF("SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) as `key`, `domain` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `mxcover` >= UNIX_TIMESTAMP() ORDER BY `domain` ASC");
            while($row = $GLOBALS['APIDB']->fetchArray($result))
                $form[] = "\t\t\t\t\t<option value='".$row['key']."'>".$row['domain']."</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='destination'>Destination:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='destination' id='destination' size='41' maxlen='255'/>&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='format'>Output Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='' name='callback'> <!-- Callback URL for PGP Key etc -->";
            $form[] = "\t\t\t\t<input type='hidden' value='newalias' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Create New Email Alias' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "uploadalias":
            $form[] = "<form name='upload-aliases' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/'.$authkey.'/uploading.api">';
            $form[] = "\t<table class='upload-aliases' id='auth-key' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='filename'>CSV List of Aliases:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='file' name='filename' id='filename' size='21' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: auto; background-color: #feedcc; padding: 10px;' colspan='2'>";
            $form[] = "\t\t\t\tThe CSV must be a standard excel or linux format and have the four captioned top row fields of: Name, Email, Alias, Domain!<br/><br/>There is two example spreedsheets with the titles in place you can populate you can download these from: <a href='" . API_URL . "/assets/docs/csv-prop-spreedsheet.xlsx' target='_blank'>csv-prop-spreedsheet.xlsx</a> or <a href='" . API_URL . "/assets/docs/csv-prop-spreedsheet.ods' target='_blank'>csv-prop-spreedsheet.ods</a>; thanks for using the example spreedsheets to generate the correct titled CSV in the right formating!";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='format'>Output Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='".$authkey."' name='authkey'>";
            $form[] = "\t\t\t\t<input type='hidden' value='alias' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Upload *.csv and propogate email aliases!' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
        case "newemail":
            $form[] = "<form name='new-record' method=\"POST\" enctype=\"multipart/form-data\" action=\"" . API_URL . '/v1/' . $authkey . '/emails.api">';
            $form[] = "\t<table class='new-record' id='auth-record' style='vertical-align: top !important; min-width: 98%;'>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='notify'>Email Address Name:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='name' id='name' size='41' maxlen='255' value='' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='email'>Email:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<input type='textbox' name='email[username]' id='email' size='23' />&nbsp;<strong style='font-size: 247%'>@</strong>&nbsp;";
            $form[] = "\t\t\t\t<select name='email[domainkey]' id='format'/>";
            $result = $GLOBALS['APIDB']->queryF("SELECT md5(concat(`id`, '" . API_URL . "', 'domain')) as `key`, `domain` FROM `" . $GLOBALS['APIDB']->prefix('domains') . "` WHERE `mxcover` >= UNIX_TIMESTAMP() ORDER BY `domain` ASC");
            while($row = $GLOBALS['APIDB']->fetchArray($result))
                $form[] = "\t\t\t\t\t<option value='".$row['key']."'>".$row['domain']."</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='size'>Password:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='password' name='password' id='size' size='41' maxlen='255' value='' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='vpass'>Verify Password:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='password' name='vpass' id='size' size='41' maxlen='255' value='' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='size'>Mailbox Size (Bytes):&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='size' id='size' size='41' maxlen='255' value='" . mt_rand((API_MINIMUM_INBOX_SIZES * 1024 * 1024 * 1024), (API_MAXIMUM_INBOX_SIZES * 1024 * 1024 * 1024)) . "' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<label for='notify'>Notification Email:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<input type='textbox' name='notify' id='notify' size='41' maxlen='255' value='' />&nbsp;&nbsp;";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td>";
            $form[] = "\t\t\t\t<label for='format'>Output Format:&nbsp;<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold'>*</font></label>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td style='width: 320px;'>";
            $form[] = "\t\t\t\t<select name='format' id='format'/>";
            $form[] = "\t\t\t\t\t<option value='raw'>RAW PHP Output</option>";
            $form[] = "\t\t\t\t\t<option value='json' selected='selected'>JSON Output</option>";
            $form[] = "\t\t\t\t\t<option value='serial'>Serialisation Output</option>";
            $form[] = "\t\t\t\t\t<option value='xml'>XML Output</option>";
            $form[] = "\t\t\t\t</select>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t\t<td>&nbsp;</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-left:64px;'>";
            $form[] = "\t\t\t\t<input type='hidden' value='newemail' name='mode'>";
            $form[] = "\t\t\t\t<input type='submit' value='Create New Email' name='submit' style='padding:11px; font-size:122%;'>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t\t\t<td colspan='3' style='padding-top: 8px; padding-bottom: 14px; padding-right:35px; text-align: right;'>";
            $form[] = "\t\t\t\t<font style='color: rgb(250,0,0); font-size: 139%; font-weight: bold;'>* </font><font  style='color: rgb(10,10,10); font-size: 99%; font-weight: bold'><em style='font-size: 76%'>~ Required Field for Form Submission</em></font>";
            $form[] = "\t\t\t</td>";
            $form[] = "\t\t</tr>";
            $form[] = "\t\t<tr>";
            $form[] = "\t</table>";
            $form[] = "</form>";
            break;
                    
    }
    return implode("\n", $form);

}


if (!function_exists("getBaseDomain")) {
    /**
     * Gets the base domain of a tld with subdomains, that is the root domain header for the network rout
     *
     * @param string $url
     *
     * @return string
     */
    function getBaseDomain($uri = '')
    {
        
        static $fallout, $strata, $classes;

        if (empty($classes))
        {
            
            $attempts = 0;
            $attempts++;
            $classes = array_keys(json_decode(getURIData(API_STRATA_API_URL ."/v1/strata/json.api", 150, 100), true));
            
        }
        if (empty($fallout))
        {
            $fallout = array_keys(json_decode(getURIData(API_STRATA_API_URL ."/v1/fallout/json.api", 150, 100), true));
        }
        
        // Get Full Hostname
        $uri = strtolower($uri);
        $hostname = parse_url($uri, PHP_URL_HOST);
        if (!filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 || FILTER_FLAG_IPV4) === false)
            return $hostname;
        
        // break up domain, reverse
        $elements = explode('.', $hostname);
        $elements = array_reverse($elements);
        
        // Returns Base Domain
        if (in_array($elements[0], $classes))
            return $elements[1] . '.' . $elements[0];
        elseif (in_array($elements[0], $fallout) && in_array($elements[1], $classes))
            return $elements[2] . '.' . $elements[1] . '.' . $elements[0];
        elseif (in_array($elements[0], $fallout))
            return  $elements[1] . '.' . $elements[0];
        else
            return  $elements[1] . '.' . $elements[0];
    }
}
?>

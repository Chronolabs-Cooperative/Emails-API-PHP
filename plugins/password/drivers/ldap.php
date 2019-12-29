<?php

/**
 * LDAP Password Driver
 *
 * Driver for passwords stored in LDAP
 * This driver use the PEAR Net_LDAP2 class (http://pear.php.net/package/Net_LDAP2).
 *
 * @version 2.0
 * @author Edouard MOREAU <edouard.moreau@ensma.fr>
 *
 * method hashPassword based on code from the phpLDAPadmin development team (http://phpldapadmin.sourceforge.net/).
 * method randomSalt based on code from the phpLDAPadmin development team (http://phpldapadmin.sourceforge.net/).
 *
 * Copyright (C) The Chronomail Dev Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 */

class chronomail_ldap_password
{
    public function save($curpass, $passwd)
    {
        $chronomail = chronomail::get_instance();
        require_once 'Net/LDAP2.php';
        require_once __DIR__ . '/ldap_simple.php';

        // Building user DN
        if ($userDN = $chronomail->config->get('password_ldap_userDN_mask')) {
            $userDN = chronomail_ldap_simple_password::substitute_vars($userDN);
        }
        else {
            $userDN = $this->search_userdn($chronomail);
        }

        if (empty($userDN)) {
            return PASSWORD_CONNECT_ERROR;
        }

        // Connection Method
        switch($chronomail->config->get('password_ldap_method')) {
            case 'admin':
                $binddn = $chronomail->config->get('password_ldap_adminDN');
                $bindpw = $chronomail->config->get('password_ldap_adminPW');
                break;
            case 'user':
            default:
                $binddn = $userDN;
                $bindpw = $curpass;
                break;
        }

        // Configuration array
        $ldapConfig = array (
            'binddn'    => $binddn,
            'bindpw'    => $bindpw,
            'basedn'    => $chronomail->config->get('password_ldap_basedn'),
            'host'      => $chronomail->config->get('password_ldap_host', 'localhost'),
            'port'      => $chronomail->config->get('password_ldap_port', '389'),
            'starttls'  => $chronomail->config->get('password_ldap_starttls'),
            'version'   => $chronomail->config->get('password_ldap_version', '3'),
        );

        // Connecting using the configuration array
        $ldap = Net_LDAP2::connect($ldapConfig);

        // Checking for connection error
        if (is_a($ldap, 'PEAR_Error')) {
            return PASSWORD_CONNECT_ERROR;
        }

        $force        = $chronomail->config->get('password_ldap_force_replace', true);
        $pwattr       = $chronomail->config->get('password_ldap_pwattr', 'userPassword');
        $lchattr      = $chronomail->config->get('password_ldap_lchattr');
        $smbpwattr    = $chronomail->config->get('password_ldap_samba_pwattr');
        $smblchattr   = $chronomail->config->get('password_ldap_samba_lchattr');
        $samba        = $chronomail->config->get('password_ldap_samba');
        $encodage     = $chronomail->config->get('password_ldap_encodage', 'crypt');

        // Support multiple userPassword values where desired.
        // multiple encodings can be specified separated by '+' (e.g. "cram-md5+ssha")
        $encodages    = explode('+', $encodage);
        $crypted_pass = array();

        foreach ($encodages as $enc) {
            if ($cpw = password::hash_password($passwd, $enc)) {
                $crypted_pass[] = $cpw;
            }
        }

        // Support password_ldap_samba option for backward compat.
        if ($samba && !$smbpwattr) {
            $smbpwattr  = 'sambaNTPassword';
            $smblchattr = 'sambaPwdLastSet';
        }

        // Crypt new password
        if (empty($crypted_pass)) {
            return PASSWORD_CRYPT_ERROR;
        }

        // Crypt new samba password
        if ($smbpwattr && !($samba_pass = password::hash_password($passwd, 'samba'))) {
            return PASSWORD_CRYPT_ERROR;
        }

        // Writing new crypted password to LDAP
        $userEntry = $ldap->getEntry($userDN);
        if (Net_LDAP2::isError($userEntry)) {
            return PASSWORD_CONNECT_ERROR;
        }

        if (!$userEntry->replace(array($pwattr => $crypted_pass), $force)) {
            return PASSWORD_CONNECT_ERROR;
        }

        // Updating PasswordLastChange Attribute if desired
        if ($lchattr) {
            $current_day = (int)(time() / 86400);
            if (!$userEntry->replace(array($lchattr => $current_day), $force)) {
                return PASSWORD_CONNECT_ERROR;
            }
        }

        // Update Samba password and last change fields
        if ($smbpwattr) {
            $userEntry->replace(array($smbpwattr => $samba_pass), $force);
        }
        // Update Samba password last change field
        if ($smblchattr) {
            $userEntry->replace(array($smblchattr => time()), $force);
        }

        if (Net_LDAP2::isError($userEntry->update())) {
            return PASSWORD_CONNECT_ERROR;
        }

        // All done, no error
        return PASSWORD_SUCCESS;
    }

    /**
     * Bind with searchDN and searchPW and search for the user's DN.
     * Use search_base and search_filter defined in config file.
     * Return the found DN.
     */
    function search_userdn($chronomail)
    {
        $binddn = $chronomail->config->get('password_ldap_searchDN');
        $bindpw = $chronomail->config->get('password_ldap_searchPW');

        $ldapConfig = array (
            'basedn'    => $chronomail->config->get('password_ldap_basedn'),
            'host'      => $chronomail->config->get('password_ldap_host', 'localhost'),
            'port'      => $chronomail->config->get('password_ldap_port', '389'),
            'starttls'  => $chronomail->config->get('password_ldap_starttls'),
            'version'   => $chronomail->config->get('password_ldap_version', '3'),
        );

        // allow anonymous searches
        if (!empty($binddn)) {
            $ldapConfig['binddn'] = $binddn;
            $ldapConfig['bindpw'] = $bindpw;
        }

        $ldap = Net_LDAP2::connect($ldapConfig);

        if (is_a($ldap, 'PEAR_Error')) {
            return '';
        }

        $base   = chronomail_ldap_simple_password::substitute_vars($chronomail->config->get('password_ldap_search_base'));
        $filter = chronomail_ldap_simple_password::substitute_vars($chronomail->config->get('password_ldap_search_filter'));
        $options = array (
            'scope' => 'sub',
            'attributes' => array(),
        );

        $result = $ldap->search($base, $filter, $options);
        if (is_a($result, 'PEAR_Error') || ($result->count() != 1)) {
            $ldap->done();
            return '';
        }
        $userDN = $result->current()->dn();
        $ldap->done();

        return $userDN;
    }
}

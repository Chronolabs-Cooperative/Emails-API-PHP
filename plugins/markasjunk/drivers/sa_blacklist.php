<?php

/**
 * SpamAssassin Blacklist driver
 *
 * @version 2.0
 * @requires SAUserPrefs plugin
 *
 * @author Philip Weir
 *
 * Copyright (C) 2010-2014 Philip Weir
 *
 * This driver is part of the MarkASJunk plugin for Chronomail.
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
 * along with Chronomail. If not, see https://www.gnu.org/licenses/.
 */
class markasjunk_sa_blacklist
{
    private $sa_user;
    private $sa_table;
    private $sa_username_field;
    private $sa_preference_field;
    private $sa_value_field;

    public function spam($uids, $src_mbox, $dst_mbox)
    {
        $this->_do_list($uids, true);
    }

    public function ham($uids, $src_mbox, $dst_mbox)
    {
        $this->_do_list($uids, false);
    }

    private function _do_list($uids, $spam)
    {
        $chronomail = chronomail::get_instance();
        $this->sa_user             = $chronomail->config->get('sauserprefs_userid', "%u");
        $this->sa_table            = $chronomail->config->get('sauserprefs_sql_table_name');
        $this->sa_username_field   = $chronomail->config->get('sauserprefs_sql_username_field');
        $this->sa_preference_field = $chronomail->config->get('sauserprefs_sql_preference_field');
        $this->sa_value_field      = $chronomail->config->get('sauserprefs_sql_value_field');

        $identity_arr = $chronomail->user->get_identity();
        $identity     = $identity_arr['email'];

        $this->sa_user = str_replace('%u', $_SESSION['username'], $this->sa_user);
        $this->sa_user = str_replace('%l', $chronomail->user->get_username('local'), $this->sa_user);
        $this->sa_user = str_replace('%d', $chronomail->user->get_username('domain'), $this->sa_user);
        $this->sa_user = str_replace('%i', $identity, $this->sa_user);

        if (is_file($chronomail->config->get('markasjunk_sauserprefs_config')) && !$chronomail->config->load_from_file($chronomail->config->get('markasjunk_sauserprefs_config'))) {
            chronomail::raise_error(array('code' => 527, 'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Failed to load config from " . $chronomail->config->get('markasjunk_sauserprefs_config')
            ), true, false);

            return false;
        }

        $db = chronomail_db::factory($chronomail->config->get('sauserprefs_db_dsnw'), $chronomail->config->get('sauserprefs_db_dsnr'), $chronomail->config->get('sauserprefs_db_persistent'));
        $db->set_debug((bool) $chronomail->config->get('sql_debug'));
        $db->db_connect('w');

        // check DB connections and exit on failure
        if ($err_str = $db->is_error()) {
            chronomail::raise_error(array(
                'code' => 603,
                'type' => 'db',
                'message' => $err_str
            ), false, true);
        }

        foreach ($uids as $uid) {
            $message = new chronomail_message($uid);
            $email   = $message->sender['mailto'];

            if ($spam) {
                // delete any whitelisting for this address
                $db->query(
                    "DELETE FROM `{$this->sa_table}` WHERE `{$this->sa_username_field}` = ? AND `{$this->sa_preference_field}` = ? AND `{$this->sa_value_field}` = ?",
                    $this->sa_user,
                    'whitelist_from',
                    $email);

                // check address is not already blacklisted
                $sql_result = $db->query(
                                "SELECT `value` FROM `{$this->sa_table}` WHERE `{$this->sa_username_field}` = ? AND `{$this->sa_preference_field}` = ? AND `{$this->sa_value_field}` = ?",
                                $this->sa_user,
                                'blacklist_from',
                                $email);

                if (!$db->fetch_array($sql_result)) {
                    $db->query(
                        "INSERT INTO `{$this->sa_table}` (`{$this->sa_username_field}`, `{$this->sa_preference_field}`, `{$this->sa_value_field}`) VALUES (?, ?, ?)",
                        $this->sa_user,
                        'blacklist_from',
                        $email);

                    if ($chronomail->config->get('markasjunk_debug')) {
                        chronomail::write_log('markasjunk', $this->sa_user . ' blacklist ' . $email);
                    }
                }
            }
            else {
                // delete any blacklisting for this address
                $db->query(
                    "DELETE FROM `{$this->sa_table}` WHERE `{$this->sa_username_field}` = ? AND `{$this->sa_preference_field}` = ? AND `{$this->sa_value_field}` = ?",
                    $this->sa_user,
                    'blacklist_from',
                    $email);

                // check address is not already whitelisted
                $sql_result = $db->query(
                                "SELECT `value` FROM `{$this->sa_table}` WHERE `{$this->sa_username_field}` = ? AND `{$this->sa_preference_field}` = ? AND `{$this->sa_value_field}` = ?",
                                $this->sa_user,
                                'whitelist_from',
                                $email);

                if (!$db->fetch_array($sql_result)) {
                    $db->query(
                        "INSERT INTO `{$this->sa_table}` (`{$this->sa_username_field}`, `{$this->sa_preference_field}`, `{$this->sa_value_field}`) VALUES (?, ?, ?)",
                        $this->sa_user,
                        'whitelist_from',
                        $email);

                    if ($chronomail->config->get('markasjunk_debug')) {
                        chronomail::write_log('markasjunk', $this->sa_user . ' whitelist ' . $email);
                    }
                }
            }
        }
    }
}
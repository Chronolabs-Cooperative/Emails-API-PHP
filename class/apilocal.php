<?php
/**
 * WhoIS REST Services API
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
 * @package         whois-api
 * @since           2.2.13
 * @author          Dr. Simon Antony Roberts <simon@snails.email>
 * @version         2.2.14
 * @description		A REST API Interface which retrieves IPv4, IPv6, TLD, gLTD Whois Data
 * @link            http://internetfounder.wordpress.com
 * @link            https://github.com/Chronolabs-Cooperative/WhoIS-API-PHP
 * @link            https://sourceforge.net/p/chronolabs-cooperative
 * @link            https://facebook.com/ChronolabsCoop
 * @link            https://twitter.com/ChronolabsCoop
 * 
 */


defined('API_ROOT_PATH') || exit('Restricted access');

/**
 * Class APILocalAbstract
 */
class APILocalAbstract
{
    /**
     * APILocalAbstract::substr()
     *
     * @param mixed  $str
     * @param mixed  $start
     * @param mixed  $length
     * @param string $trimmarker
     *
     * @return mixed|string
     */
    public static function substr($str, $start, $length, $trimmarker = '...')
    {
        if (!API_USE_MULTIBYTES) {
            return (strlen($str) - $start <= $length) ? substr($str, $start, $length) : substr($str, $start, $length - strlen($trimmarker)) . $trimmarker;
        }
        if (function_exists('mb_internal_encoding') && @mb_internal_encoding(_CHARSET)) {
            $str2 = mb_strcut($str, $start, $length - strlen($trimmarker));

            return $str2 . (mb_strlen($str) != mb_strlen($str2) ? $trimmarker : '');
        }

        return $str;
    }
    // Each local language should define its own equalient utf8_encode
    /**
     * APILocalAbstract::utf8_encode()
     *
     * @param  mixed $text
     * @return string
     */
    public static function utf8_encode($text)
    {
        if (API_USE_MULTIBYTES == 1) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($text, 'UTF-8', 'auto');
            }
        }

        return utf8_encode($text);
    }

    /**
     * APILocalAbstract::convert_encoding()
     *
     * @param  mixed  $text
     * @param  string $to
     * @param  string $from
     * @return mixed|string
     */
    public static function convert_encoding($text, $to = 'utf-8', $from = '')
    {
        if (empty($text)) {
            return $text;
        }
        if (empty($from)) {
            $from = empty($GLOBALS['xlanguage']['charset_base']) ? _CHARSET : $GLOBALS['xlanguage']['charset_base'];
        }
        if (empty($to) || !strcasecmp($to, $from)) {
            return $text;
        }

        if (API_USE_MULTIBYTES && function_exists('mb_convert_encoding')) {
            $converted_text = @mb_convert_encoding($text, $to, $from);
        } elseif (function_exists('iconv')) {
            $converted_text = @iconv($from, $to . '//TRANSLIT', $text);
        } elseif ('utf-8' === $to) {
            $converted_text = utf8_encode($text);
        }
        $text = empty($converted_text) ? $text : $converted_text;

        return $text;
    }

    /**
     * APILocalAbstract::trim()
     *
     * @param  mixed $text
     * @return string
     */
    public static function trim($text)
    {
        $ret = trim($text);

        return $ret;
    }

    /**
     * Get description for setting time format
     */
    public static function getTimeFormatDesc()
    {
        return _TIMEFORMAT_DESC;
    }

    /**
     * Function to display formatted times in user timezone
     *
     * Setting $timeoffset to null (by default) will skip timezone calculation for user, using default timezone instead, which is a MUST for cached contents
     * @param        $time
     * @param string $format
     * @param null|string   $timeoffset
     * @return string
     */
    public static function formatTimestamp($time, $format = 'l', $timeoffset = null)
    {
        global $apiConfig, $apiUser;

        $format_copy = $format;
        $format      = strtolower($format);

        if ($format === 'rss' || $format === 'r') {
            $TIME_ZONE = '';
            if (isset($GLOBALS['apiConfig']['server_TZ'])) {
                $server_TZ = abs((int)($GLOBALS['apiConfig']['server_TZ'] * 3600.0));
                $prefix    = ($GLOBALS['apiConfig']['server_TZ'] < 0) ? ' -' : ' +';
                $TIME_ZONE = $prefix . date('Hi', $server_TZ);
            }
            $date = gmdate('D, d M Y H:i:s', (int)$time) . $TIME_ZONE;

            return $date;
        }

        if (($format === 'elapse' || $format === 'e') && $time < time()) {
            $elapse = time() - $time;
            if ($days = floor($elapse / (24 * 3600))) {
                $num = $days > 1 ? sprintf(_DAYS, $days) : _DAY;
            } elseif ($hours = floor(($elapse % (24 * 3600)) / 3600)) {
                $num = $hours > 1 ? sprintf(_HOURS, $hours) : _HOUR;
            } elseif ($minutes = floor(($elapse % 3600) / 60)) {
                $num = $minutes > 1 ? sprintf(_MINUTES, $minutes) : _MINUTE;
            } else {
                $seconds = $elapse % 60;
                $num     = $seconds > 1 ? sprintf(_SECONDS, $seconds) : _SECOND;
            }
            $ret = sprintf(_ELAPSE, $num);

            return $ret;
        }
        // disable user timezone calculation and use default timezone,
        // for cache consideration
        if ($timeoffset === null) {
            $timeoffset = ($apiConfig['default_TZ'] == '') ? '0.0' : $apiConfig['default_TZ'];
        }
        $usertimestamp = api_getUserTimestamp($time, $timeoffset);
        switch ($format) {
            case 's':
                $datestring = _SHORTDATESTRING;
                break;

            case 'm':
                $datestring = _MEDIUMDATESTRING;
                break;

            case 'mysql':
                $datestring = 'Y-m-d H:i:s';
                break;

            case 'l':
                $datestring = _DATESTRING;
                break;

            case 'c':
            case 'custom':
                static $current_timestamp, $today_timestamp, $monthy_timestamp;
                if (!isset($current_timestamp)) {
                    $current_timestamp = api_getUserTimestamp(time(), $timeoffset);
                }
                if (!isset($today_timestamp)) {
                    $today_timestamp = mktime(0, 0, 0, date('m', $current_timestamp), date('d', $current_timestamp), date('Y', $current_timestamp));
                }

                if (abs($elapse_today = $usertimestamp - $today_timestamp) < 24 * 60 * 60) {
                    $datestring = ($elapse_today > 0) ? _TODAY : _YESTERDAY;
                } else {
                    if (!isset($monthy_timestamp)) {
                        $monthy_timestamp[0] = mktime(0, 0, 0, 0, 0, date('Y', $current_timestamp));
                        $monthy_timestamp[1] = mktime(0, 0, 0, 0, 0, date('Y', $current_timestamp) + 1);
                    }
                    $datestring = _YEARMONTHDAY;
                    if ($usertimestamp >= $monthy_timestamp[0] && $usertimestamp < $monthy_timestamp[1]) {
                        $datestring = _MONTHDAY;
                    }
                }
                break;

            default:
                $datestring = _DATESTRING;
                if ($format != '') {
                    $datestring = $format_copy;
                }
                break;
        }

        return ucfirst(date($datestring, $usertimestamp));
    }

    /**
     * APILocalAbstract::number_format()
     *
     * @param  mixed $number
     * @return mixed
     */
    public function number_format($number)
    {
        return $number;
    }

    /**
     * APILocalAbstract::money_format()
     *
     * @param  mixed $format
     * @param  mixed $number
     * @return mixed
     */
    public function money_format($format, $number)
    {
        return $number;
    }

    /**
     * APILocalAbstract::__call()
     *
     * @param  mixed $name
     * @param  mixed $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (function_exists($name)) {
            return call_user_func_array($name, $args);
        }
        return null;
    }
}

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

require_once './include/common.inc.php';
defined('API_INSTALL') || die('API Installation wizard die');

$pageHasForm = false;
$diagsOK     = false;

foreach ($wizard->configs['extensions'] as $ext => $value) {
    if (extension_loaded($ext)) {
        if (is_array($value[0])) {
            $wizard->configs['extensions'][$ext][] = xoDiag(1, implode(',', $value[0]));
        } else {
            $wizard->configs['extensions'][$ext][] = xoDiag(1, $value[0]);
        }
    } else {
        $wizard->configs['extensions'][$ext][] = xoDiag(0, $value[0]);
    }
}
ob_start();
?>
    <h3><?php echo REQUIREMENTS; ?></h3>
    <table class="table table-hover">
        <tbody>
        <tr>
            <th><?php echo SERVER_API; ?></th>
            <td><?php echo php_sapi_name(); ?><br> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
        </tr>

        <tr>
            <th><?php echo _PHP_VERSION; ?></th>
            <td><?php echo xoPhpVersion(); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'MySQLi'); ?></th>
            <td><?php echo xoDiag(function_exists('mysqli_connect') ? 1 : -1, @mysqli_get_client_info()); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Sockets'); ?></th>
            <td><?php echo xoDiag(extension_loaded('sockets') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Session'); ?></th>
            <td><?php echo xoDiag(extension_loaded('session') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'FileInfo'); ?></th>
            <td><?php echo xoDiag(extension_loaded('fileinfo') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Libiconv'); ?></th>
            <td><?php echo xoDiag(extension_loaded('libiconv') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Multibyte'); ?></th>
            <td><?php echo xoDiag(extension_loaded('multibyte') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'OpenSSL'); ?></th>
            <td><?php echo xoDiag(extension_loaded('openssl') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'JSON'); ?></th>
            <td><?php echo xoDiag(extension_loaded('json') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'DOM'); ?></th>
            <td><?php echo xoDiag(extension_loaded('dom') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Intl'); ?></th>
            <td><?php echo xoDiag(extension_loaded('intl') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Exif'); ?></th>
            <td><?php echo xoDiag(extension_loaded('exif') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'oci8'); ?></th>
            <td><?php echo xoDiag(extension_loaded('oci8') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'PDO'); ?></th>
            <td><?php echo xoDiag(extension_loaded('pdo') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'LDAP'); ?></th>
            <td><?php echo xoDiag(extension_loaded('ldap') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'GD'); ?></th>
            <td><?php echo xoDiag(extension_loaded('gd') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Imagick'); ?></th>
            <td><?php echo xoDiag(extension_loaded('imagick') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'Zip'); ?></th>
            <td><?php echo xoDiag(extension_loaded('zip') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th><?php printf(PHP_EXTENSION, 'pdo_mysql'); ?></th>
            <td><?php echo xoDiag(extension_loaded('pdo_mysql') ? 1 : -1); ?></td>
        </tr>

        <tr>
            <th scope="row">file_uploads</th>
            <td><?php echo xoDiagBoolSetting('file_uploads', true); ?></td>
        </tr>

        <tr>
            <th scope="row">allow_url_fopen</th>
            <td><?php echo xoDiagBoolSetting('allow_url_fopen', true); ?></td>
        </tr>

        </tbody>
    </table>

    <h3><?php echo RECOMMENDED_EXTENSIONS; ?></h3>
    <table class="table table-hover">
        <caption><?php echo RECOMMENDED_EXTENSIONS_MSG; ?></caption>
        <tbody>
        <?php
        foreach ($wizard->configs['extensions'] as $key => $value) {
            echo '<tr><th>' . $value[2] . '</th><td>' . $value[1] . '</td></tr>';
        }
        ?>

        </tbody>
    </table>
<?php
$content = ob_get_contents();
ob_end_clean();

include './include/install_tpl.php';

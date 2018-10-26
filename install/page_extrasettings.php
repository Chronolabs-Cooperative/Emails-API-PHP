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

$wizard->loadLangFile('extras');

include_once './include/functions.php';

$pageHasForm = true;
$pageHasHelp = true;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && @$_GET['var'] && @$_GET['action'] === 'checkfile') {
    $file                   = $_GET['var'];
    echo genPathCheckHtml($file, is_file($file));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = array();
    foreach($wizard->configs['api_url'] as $setting => $values)
    {
        $_SESSION['constants']['api_url'][$setting] = $_POST[$setting];
    }
    foreach($wizard->configs['path'] as $setting => $values)
    {
        $_SESSION['constants']['path'][$setting] = $_POST[$setting];
    }
    foreach($wizard->configs['api_user'] as $setting => $values)
    {
        $_SESSION['constants']['api_user'][$setting] = $_POST['api_user_' . $setting];
    }
    foreach($wizard->configs['api_pass'] as $setting => $values)
    {
        $_SESSION['constants']['api_pass'][$setting] = $_POST['api_pass_' . $setting];
    }
    foreach($wizard->configs['api_urls'] as $setting => $values)
    {
        $_SESSION['constants']['api_url'][$setting] = $_POST['api_url_' . $setting];
    }
    foreach($wizard->configs['pgp_keys'] as $setting => $values)
    {
        $_SESSION['constants']['pgp_keys'][$setting] = $_POST[$setting];
    }
    foreach($wizard->configs['inbox_sizes'] as $setting => $values)
    {
        $_SESSION['constants']['inbox_sizes'][$setting] = $_POST[$setting];
    }
    foreach($wizard->configs['service_hostname'] as $setting => $values)
    {
        $_SESSION['constants']['service_hostname'][$setting] = $_POST[$setting];
    }
    $wizard->redirectToPage('+1');
    return 302;
}
ob_start();
?>
    <script type="text/javascript">
        function removeTrailing(id, val) {
            if (val[val.length - 1] == '/') {
                val = val.substr(0, val.length - 1);
                $(id).value = val;
            }

            return val;
        }

        function existingFile(key, val) {
            val = removeTrailing(key, val);
            $.get( "<?php echo $_SERVER['PHP_SELF']; ?>", { action: "checkfile", var: key, path: val } )
                .done(function( tmp ) {
                    $("#" + key + 'fileimg').html(tmp);
                });
            $("#" + key + 'perms').style.display = 'none';
        }
    </script>
    <div class="panel panel-info">
        <div class="panel-heading"><?php echo API_EXTRAS; ?></div>
        <div class="panel-body">
        <div class="form-group">
            <?php 
            foreach($wizard->configs['api_url'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['api_urls'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo 'api_url_' . $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['api_user'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_USER_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_USER_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo 'api_user_' . $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['api_pass'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_PASS_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_PASS_HELP"); ?></div>
                <input type="password" class="form-control" name="<?php echo 'api_pass_' . $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['path'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['pgp_keys'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_PGPKEYS_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_PGPKEYS_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['inbox_sizes'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_INBOXMBS_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_INBOXMBS_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            foreach($wizard->configs['service_hostname'] as $setting => $default)
            {?>
            <label for="<?php echo $setting; ?>"><?php echo constant("API_".strtoupper($setting) . "_HOSTNAME_LABEL"); ?></label>
                <div class="xoform-help alert alert-info"><?php echo constant("API_".strtoupper($setting) . "_HOSTNAME_HELP"); ?></div>
                <input type="text" class="form-control" name="<?php echo $setting; ?>" id="<?php echo $setting; ?>" value="<?php echo $default; ?>"/>
            <?php }
            ?>
       </div>
   </div>

<?php
$content = ob_get_contents();
ob_end_clean();

include './include/install_tpl.php';

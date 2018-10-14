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

    global $email, $inner;
            
            
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta property="og:title" content="<?php echo API_VERSION; ?>"/>
    <meta property="og:type" content="api<?php echo API_TYPE; ?>"/>
    <meta property="og:image" content="<?php echo API_URL; ?>/assets/images/logo_500x500.png"/>
    <meta property="og:url" content="<?php echo (isset($_SERVER["HTTPS"])?"https://":"http://").$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]; ?>" />
    <meta property="og:site_name" content="<?php echo API_VERSION; ?> - <?php echo API_LICENSE_COMPANY; ?>"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="rating" content="general" />
    <meta http-equiv="author" content="wishcraft@users.sourceforge.net" />
    <meta http-equiv="copyright" content="<?php echo API_LICENSE_COMPANY; ?> &copy; <?php echo date("Y"); ?>" />
    <meta http-equiv="generator" content="Chronolabs Cooperative (<?php echo $place['iso3']; ?>)" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $email['name']; ?> || <?php echo $email['email']; ?></title>
    <link rel="stylesheet" href="<?php echo API_URL; ?>/assets/css/style.css" type="text/css" />
    <link href="<?php echo API_URL; ?>/assets/media/Labtop/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Bold/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Bold Italic/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Italic/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Superwide Boldish/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Thin/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Labtop Unicase/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/LHF Matthews Thin/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Life BT Bold/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Life BT Bold Italic/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Prestige Elite/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Prestige Elite Bold/style.css" rel="stylesheet" type="text/css">
    <link href="<?php echo API_URL; ?>/assets/media/Prestige Elite Normal/style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="<?php echo API_URL; ?>/assets/css/gradients.php" type="text/css" />
    <link rel="stylesheet" href="<?php echo API_URL; ?>/assets/css/shadowing.php" type="text/css" />
</head>
<body>
<div class="main">
	<img style="float: right; margin: 11px; width: auto; height: auto; clear: none;" src="<?php echo API_URL; ?>/assets/images/logo_350x350.png" />
    <h1>&quot;<?php echo $email['name']; ?>&quot;&nbsp;&lt;<?php echo $email['email']; ?>&gt;</h1>
    <p>Your email address of <strong><?php echo $email['email']; ?></strong>; is now active and online, below you can change the notification email as well as password for it now if you wish, you will only be able to do this now and not return too this page.</p>
    <h2>Change your password</h2>
    <p>From here you change your password for the email address of: <?php echo $email['email']; ?></p>
    <?php if (!empty($inner['pass']) && !empty($inner['vpass']) && $inner['pass'] != $inner['vpass']) { ?>
    <p style="padding: 9px; border: 3px; border-color: rgb(255, 0, 0); background-color: rgb(200, 0, 0); font-weight: bold; font-size: 1.32em; color: rgb(90, 0, 0); margin: 25px; text-align: center;">
    	The Two Passwords you have Entered; both the Password and the Verification of the Same Password don't match!
   	</p>
   	<?php } elseif (!empty($inner['pass']) && empty($inner['vpass'])) { ?>
    <p style="padding: 9px; border: 3px; border-color: rgb(255, 0, 0); background-color: rgb(200, 0, 0); font-weight: bold; font-size: 1.32em; color: rgb(90, 0, 0); margin: 25px; text-align: center;">
    	You have not put in a verification of the same password you want to set this too!
   	</p>
   	<?php } elseif (empty($inner['pass']) && !empty($inner['vpass'])) { ?>
    <p style="padding: 9px; border: 3px; border-color: rgb(255, 0, 0); background-color: rgb(200, 0, 0); font-weight: bold; font-size: 1.32em; color: rgb(90, 0, 0); margin: 25px; text-align: center;">
    	You have not put in a password change of the same password you want to set this too!
   	</p>
   	<?php }?>
    <blockquote>
        <?php echo getHTMLForm('changepass'); ?>
    </blockquote>
    <h2>Change your notifications email</h2>
    <p>From here you change your notification/recovery for the email address it is currently set to: <?php echo $email['notify']; ?>!</p>
    <?php if (!empty($inner['notify']) && !checkEmail($inner['notify'])) { ?>
    <p style="padding: 9px; border: 3px; border-color: rgb(255, 0, 0); background-color: rgb(200, 0, 0); font-weight: bold; font-size: 1.32em; color: rgb(90, 0, 0); margin: 25px; text-align: center;">
    	You have not entered an email address as:&nbsp;<em><?php echo $inner['notify']; ?></em>
   	</p>
   	<?php } ?>
    <blockquote>
        <?php echo getHTMLForm('changenotify'); ?>
    </blockquote>
    </div>
</html>
<?php 

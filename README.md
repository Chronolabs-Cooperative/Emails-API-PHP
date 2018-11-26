## Chronolabs Cooperative presents

# Emails/Aliases Account's Propogation REST API Services

## Version: 1.0.7 (stable)

### Author: Dr. Simon Antony Roberts <simon@ordinance.space>

#### Demo: http://emails.snails.email

[![Emails/Aliases Account's Propogation REST API Service](http://img.youtube.com/vi/qCABctfNaaA/0.jpg)](http://www.youtube.com/watch?v=qCABctfNaaA)

This API allows for a REST API to generate email addresses with IMAP, POP, SMTP resolve as well as maintenance and a client directory for each domain which can be assigned. The installation include configuration of dovecove in a number of linux formats, but you will have to still configure manually your postfix smtp relay settings, these are not included in the instructions for installation.

This REST API allows for configuration of email account remotely from other websites and sources via REST API calling, and it is protected by the requirement of using an admin username and password for this defining purposes!

In conjunctions with the Zones DNS Rest API and PowerDNS (see: https://github.com/Chronolabs-Cooperative/Zones-API-PHP) this API will automatically mount your Open PGP Armor Keys in the domain record type and allow auto discovery happen with the email in inbound+outbound sending emails to be network transit layer encrypted!

# Apache Mod Rewrite (SEO Friendly URLS)

The follow lines go in your API_ROOT_PATH/.htaccess

    php_value memory_limit 24M
    php_value upload_max_filesize 1M
    php_value post_max_size 1M
    php_value error_reporting 0
    php_value display_errors 0
        
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^v([0-9]{1,2})/test.api ./index.php?version=$1&mode=test [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/peers.api ./peers.php?version=$1&mode=peers [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/callback.api ./callback.php?version=$1&mode=callback [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-zA-Z])/callback.api ./callback.php?version=$1&mode=$2 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-zA-Z])/([0-9a-z]{32})/callback.api ./callback.php?version=$1&mode=$2&key=$3 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/authkey.api ./index.php?version=$1&mode=authkey [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/pgpkey.api ./index.php?version=$1&mode=pgpkey [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-z]{32})/([0-9a-z])/activation.html ./activation.php?version=$1&mode=activation&emailkey=$2&actkey=$3 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-z]{32})/(aliases|emails|domains).api ./index.php?version=$1&authkey=$2&mode=$3 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-z]{32})/(aliases|domains|users)/(raw|html|serial|json|xml).api ./index.php?version=$1&authkey=$2&mode=$3&format=$4 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-z]{32})/([0-9a-z]{32})/(emails|pgpkeys|aliases)/(raw|html|serial|json|xml).api ./index.php?version=$1&authkey=$2&key=$3&mode=$4&format=$5 [L,NC,QSA]
    RewriteRule ^v([0-9]{1,2})/([0-9a-z]{32})/([0-9a-z]{32})/(edit|delete)/(email|alias|domain|user)/(raw|html|serial|json|xml).api ./index.php?version=$1&authkey=$2&key=$3&mode=$4&type=$5&format=$6 [L,NC,QSA]

## Scheduled Cron Job Details.,
    
There is one or more cron jobs that is scheduled task that need to be added to your system kernel when installing this API, the following command is before you install the chronological jobs with crontab in debain/ubuntu
    
    Execute:-
    $ sudo crontab -e


### CronTab Entry:

You have to add the following cronjobs to your cronjobs or on windows scheduled tasks!

    */41 */2 * * * /usr/bin/php /var/www/emails.snails.email/crons/find-mx-services.php
    */41 */3 * * * /usr/bin/php /var/www/emails.snails.email/crons/import-emailed-keys.php
    */11 * * * * /usr/bin/php /var/www/emails.snails.email/crons/import-generated-keys.php
    */22 * * * * /usr/bin/php /var/www/emails.snails.email/crons/port-encryption-keys.php
    */11 */7 * * * /usr/bin/php /var/www/emails.snails.email/crons/peer-services.php
    */11 */7 * * * /usr/bin/php /var/www/emails.snails.email/crons/get-spam-training.php
    */5 * * * * sh /var/www/emails.snails.email/crons/*.sh
    
    
## Licensing

 * This is released under General Public License 3 - GPL3 + ACADEMIC!

# Installation

Copy the contents of this archive/repository to the run time environment, configue apache2, ngix or iis to resolve the path of this repository and run the HTML Installer.

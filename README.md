## Chronolabs Cooperative presents

# Emails REST API Services

## Version: 1.0.1 (pre-alpha)

### Author: Dr. Simon Antony Roberts <simon@snails.email>

#### Demo: http://emails.snails.email

This API allows for a REST API to generate email addresses with IMAP, POP, SMTP resolve as well as maintenance and a client directory for each domain which can be assigned. The installation include configuration of dovecove in a number of linux formats, but you will have to still configure manually your postfix smtp relay settings, these are not included in the instructions for installation.

This REST API allows for configuration of email account remotely from other websites and sources via REST API calling, and it is protected by the requirement of using an admin username and password for this defining purposes!

# Apache Mod Rewrite (SEO Friendly URLS)

The follow lines go in your API_ROOT_PATH/.htaccess

    php_value memory_limit 16M
    php_value upload_max_filesize 1M
    php_value post_max_size 1M
    php_value error_reporting 0
    php_value display_errors 0
    
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    RewriteRule ^v([0-9]{1,2})/(.*?)/(json|xml|serial|raw|html).api$ ./index.php?version=$1&whois=$2&output=$3 [L,NC,QSA]

## Scheduled Cron Job Details.,
    
There is one or more cron jobs that is scheduled task that need to be added to your system kernel when installing this API, the following command is before you install the chronological jobs with crontab in debain/ubuntu
    
    Execute:-
    $ sudo crontab -e

### CronTab Entry:
    
    
## Licensing

 * This is released under General Public License 3 - GPL3 - Only!

# Installation

Copy the contents of this archive/repository to the run time environment, configue apache2, ngix or iis to resolve the path of this repository and run the HTML Installer.
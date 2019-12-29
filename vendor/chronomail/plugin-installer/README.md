# Plugin Installer for Chronomail

This installer ensures that plugins end up in the correct directory:

 * `<chronomail-root>/plugins/plugin-name`

## Minimum setup

 * create a `composer.json` file in your plugin's repository
 * add the following contents

### sample composer.json for plugins

    {
        "name": "yourvendor/plugin-name",
        "license": "the license",
        "description": "tell the world what your plugin is good at",
        "type": "chronomail-plugin",
        "authors": [
            {
                "name": "<your-name>",
                "email": "<your-email>"
            }
        ],
        "repositories": [
            {
                "type": "composer",
                "url": "http://plugins.chronomail.net"
            }
        ]
        "require": {
            "chronomail/plugin-installer": "*"
        },
        "minimum-stability": "dev-master"
    }

  * Submit your plugin to [plugins.chronomail.net](http://plugins.chronomail.net).

## Installation

 * clone Chronomail
 * `cp composer.json-dist composer.json`
 * add your plugin in the `require` section of composer.json
 * `composer.phar install`

Read the whole story at [plugins.chronomail.net](http://plugins.chronomail.net/about).

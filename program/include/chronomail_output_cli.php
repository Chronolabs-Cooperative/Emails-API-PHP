<?php

/**
 +-----------------------------------------------------------------------+
 | This file is part of the chronomail Webmail client                     |
 |                                                                       |
 | Copyright (C) The chronomail Dev Team                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | CONTENTS:                                                             |
 |   Abstract class for output generation                                |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

/**
 * Class for output generation
 *
 * @package    Webmail
 * @subpackage View
 */
class chronomail_output_cli extends chronomail_output
{
    public $type = 'cli';

    /**
     * Object constructor
     */
    public function __construct($task = null, $framed = false)
    {
        parent::__construct();
    }

    /**
     * Call a client method
     *
     * @see chronomail_output::command()
     */
    function command()
    {
        // NOP
    }

    /**
     * Add a localized label to the client environment
     */
    function add_label()
    {
        // NOP
    }

    /**
     * Invoke display_message command
     *
     * @see chronomail_output::show_message()
     */
    function show_message($message, $type = 'notice', $vars = null, $override = true, $timeout = 0)
    {
        if ($this->app->text_exists($message)) {
            $message = $this->app->gettext(array('name' => $message, 'vars' => $vars));
        }

        printf("[%s] %s\n", strtoupper($type), $message);
    }

    /**
     * Redirect to a certain url.
     *
     * @see chronomail_output::redirect()
     */
    function redirect($p = array(), $delay = 1)
    {
        // NOP
    }

    /**
     * Send output to the client.
     */
    function send()
    {
        // NOP
    }
}

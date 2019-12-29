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
 | PURPOSE:                                                              |
 |   Fetch message headers in raw format for display                     |
 +-----------------------------------------------------------------------+
 | Author: Aleksander Machniak <alec@alec.pl>                            |
 +-----------------------------------------------------------------------+
*/

$uid    = chronomail_utils::get_input_value('_uid', chronomail_utils::INPUT_GP);
$inline = $OUTPUT instanceof chronomail_output_html;

if ($uid) {
    if ($pos = strpos($uid, '.')) {
        $message = new chronomail_message($uid);
        $source  = $message->get_part_body(substr($uid, $pos + 1));
        $source  = substr($source, 0, strpos($source, "\r\n\r\n"));
    }
    else {
        $source = $RCMAIL->storage->get_raw_headers($uid);
    }

    if ($source !== false) {
        $source = trim(chronomail_charset::clean($source));
        $source = htmlspecialchars($source, ENT_COMPAT | ENT_HTML401, RCUBE_CHARSET);
        $source = preg_replace(
            array(
                '/\n[\t\s]+/',
                '/^([a-z0-9_:-]+)/im',
                '/\r?\n/'
            ),
            array(
                "\n&nbsp;&nbsp;&nbsp;&nbsp;",
                '<font class="bold">\1</font>',
                '<br />'
            ), $source);

        $OUTPUT->add_handlers(array('dialogcontent' => 'chronomail_headers_output'));

        if ($inline) {
            $OUTPUT->set_env('dialog_class', 'text-nowrap');
        }
        else {
            $OUTPUT->command('set_headers', $source);
        }
    }
    else if (!$inline) {
        $RCMAIL->output->show_message('messageopenerror', 'error');
    }

    $OUTPUT->send($inline ? 'dialog' : null);
}


function chronomail_headers_output()
{
    global $source;
    return $source;
}

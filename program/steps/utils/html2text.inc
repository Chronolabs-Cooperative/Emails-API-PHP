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
 |   Convert HTML message to plain text                                  |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

$html = stream_get_contents(fopen('php://input', 'r'));

$params['links'] = (bool) chronomail_utils::get_input_value('_do_links', chronomail_utils::INPUT_GET);
$params['width'] = (int) chronomail_utils::get_input_value('_width', chronomail_utils::INPUT_GET);

$text = $RCMAIL->html2text($html, $params);

header('Content-Type: text/plain; charset=' . RCUBE_CHARSET);
print $text;
exit;

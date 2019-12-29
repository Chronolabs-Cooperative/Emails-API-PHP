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
 |   Convert plain text to HTML                                          |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <chronomail@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

$text = stream_get_contents(fopen('php://input', 'r'));

$converter = new chronomail_text2html($text, false, array('wrap' => true));

header('Content-Type: text/html; charset=' . RCUBE_CHARSET);
print $converter->get_html();
exit;

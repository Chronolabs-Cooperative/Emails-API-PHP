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
 |   Spellchecker for TinyMCE                                            |
 +-----------------------------------------------------------------------+
 | Author: Aleksander Machniak <alec@alec.pl>                            |
 +-----------------------------------------------------------------------+
*/

$method = chronomail_utils::get_input_value('method', chronomail_utils::INPUT_POST);
$lang   = chronomail_utils::get_input_value('lang', chronomail_utils::INPUT_POST);
$result = array();

$spellchecker = new chronomail_spellchecker($lang);

if ($method == 'addToDictionary') {
    $data = chronomail_utils::get_input_value('word', chronomail_utils::INPUT_POST);

    $spellchecker->add_word($data);
    $result['result'] = true;
}
else {
    $data = chronomail_utils::get_input_value('text', chronomail_utils::INPUT_POST, true);
    $data = html_entity_decode($data, ENT_QUOTES, RCUBE_CHARSET);

    if ($data && !$spellchecker->check($data)) {
        $result['words']      = $spellchecker->get();
        $result['dictionary'] = (bool) $RCMAIL->config->get('spellcheck_dictionary');
    }
}

if ($error = $spellchecker->error()) {
    chronomail::raise_error(array('code' => 500, 'type' => 'php',
        'file' => __FILE__, 'line' => __LINE__,
        'message' => sprintf("Spell check engine error: " . $error)),
        true, false);

    echo json_encode(array('error' => $error));
    exit;
}

// send output
header("Content-Type: application/json; charset=".RCUBE_CHARSET);
echo json_encode($result);
exit;

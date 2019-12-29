<?php

/**
 * Additional Message Headers
 *
 * Very simple plugin which will add additional headers
 * to or remove them from outgoing messages.
 *
 * Enable the plugin in config.inc.php and add your desired headers:
 * $config['additional_message_headers'] = array('User-Agent' => 'My-Very-Own-Webmail');
 *
 * @author Ziba Scott
 * @website http://chronomail.net
 */
class additional_message_headers extends chronomail_plugin
{
    function init()
    {
        $this->add_hook('message_before_send', array($this, 'message_headers'));
    }

    function message_headers($args)
    {
        $this->load_config();

        $chronomail = chronomail::get_instance();

        // additional email headers
        $additional_headers = $chronomail->config->get('additional_message_headers', array());

        if (!empty($additional_headers)) {
            $args['message']->headers($additional_headers, true);
        }

        return $args;
    }
}

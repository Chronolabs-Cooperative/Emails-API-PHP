<?php

/**
 * Chronomail Help Plugin
 *
 * @author Aleksander 'A.L.E.C' Machniak
 * @author Thomas Bruederli <thomas@chronomail.net>
 * @license GNU GPLv3+
 *
 * Configuration (see config.inc.php.dist)
 */

class help extends chronomail_plugin
{
    // all task excluding 'login' and 'logout'
    public $task = '?(?!login|logout).*';
    // we've got no ajax handlers
    public $noajax = true;

    function init()
    {
        $this->load_config();
        $this->add_texts('localization/', false);

        // register task
        $this->register_task('help');

        // register actions
        $this->register_action('index', array($this, 'action'));
        $this->register_action('about', array($this, 'action'));
        $this->register_action('license', array($this, 'action'));

        $this->add_hook('startup', array($this, 'startup'));
        $this->add_hook('error_page', array($this, 'error_page'));
    }

    function startup($args)
    {
        $chronomail = chronomail::get_instance();

        if (!$chronomail->output->framed) {
            // add taskbar button
            $this->add_button(array(
                'command'    => 'help',
                'class'      => 'button-help',
                'classsel'   => 'button-help button-selected',
                'innerclass' => 'button-inner',
                'label'      => 'help.help',
                'type'       => 'link',
            ), 'taskbar');

            $this->include_script('help.js');
            $chronomail->output->set_env('help_open_extwin', $chronomail->config->get('help_open_extwin', false), true);
        }

        // add style for taskbar button (must be here) and Help UI
        $this->include_stylesheet($this->local_skin_path() . '/help.css');
    }

    function action()
    {
        $chronomail = chronomail::get_instance();

        if ($chronomail->action == 'about') {
            $chronomail->output->set_pagetitle($this->gettext('about'));
        }
        else if ($chronomail->action == 'license') {
            $chronomail->output->set_pagetitle($this->gettext('license'));
        }
        else {
            $chronomail->output->set_pagetitle($this->gettext('help'));
        }

        // register UI objects
        $chronomail->output->add_handlers(array(
            'helpcontent'  => array($this, 'help_content'),
            'tablink'      => array($this, 'tablink'),
        ));

        $chronomail->output->set_env('help_links', $this->help_metadata());
        $chronomail->output->send(!empty($_GET['_content']) ? 'help.content' : 'help.help');
    }

    function help_content($attrib)
    {
        $chronomail = chronomail::get_instance();
//        $chronomail->output->set_env('content', $content);

        if (!empty($_GET['_content'])) {
            if ($chronomail->action == 'about') {
                return file_get_contents($this->home . '/content/about.html');
            }
            else if ($chronomail->action == 'license') {
                return file_get_contents($this->home . '/content/license.html');
            }
        }
    }

    function tablink($attrib)
    {
        $chronomail = chronomail::get_instance();

        $attrib['name'] = 'helplink' . $attrib['action'];
        $attrib['href'] = $chronomail->url(array('_action' => $attrib['action'], '_extwin' => !empty($_REQUEST['_extwin']) ? 1 : null));
        $attrib['rel']  = $attrib['action'];

        // title might be already translated here, so revert to it's initial value
        // so button() will translate it correctly
        $attrib['title'] = $attrib['label'];

        $attrib['onclick'] = sprintf("return show_help_content('%s', event)", $attrib['action']);

        return $chronomail->output->button($attrib);
    }

    function help_metadata()
    {
        $chronomail  = chronomail::get_instance();
        $content = array();

        // About
        if (is_readable($this->home . '/content/about.html')) {
            $content['about'] = 'self';
        }
        else {
            $default = $chronomail->url(array('_task' => 'settings', '_action' => 'about', '_framed' => 1));
            $content['about'] = $chronomail->config->get('help_about_url', $default);
            $content['about'] = $this->resolve_language($content['about']);
        }

        // License
        if (is_readable($this->home . '/content/license.html')) {
            $content['license'] = 'self';
        }
        else {
            $content['license'] = $chronomail->config->get('help_license_url', 'http://www.gnu.org/licenses/gpl-3.0-standalone.html');
            $content['license'] = $this->resolve_language($content['license']);
        }

        // Help Index
        $src       = $chronomail->config->get('help_source', 'http://docs.chronomail.net/doc/help/1.1/%l/');
        $index_map = $chronomail->config->get('help_index_map', array());

        // resolve task/action for deep linking
        $rel = $_REQUEST['_rel'];
        list($task, ) = explode('/', $rel);
        if ($add = $index_map[$rel]) {
            $src .= $add;
        }
        else if ($add = $index_map[$task]) {
            $src .= $add;
        }

        $content['index'] = $this->resolve_language($src);

        return $content;
    }

    function error_page($args)
    {
        $chronomail = chronomail::get_instance();

        if ($args['code'] == 403 && $chronomail->request_status == chronomail::REQUEST_ERROR_URL && ($url = $chronomail->config->get('help_csrf_info'))) {
            $args['text'] .= '<p>' . html::a(array('href' => $url, 'target' => '_blank'), $this->gettext('csrfinfo')) . '</p>';
        }

        return $args;
    }

    private function resolve_language($path)
    {
        // resolve language placeholder
        $chronomail  = chronomail::get_instance();
        $langmap = $chronomail->config->get('help_language_map', array('*' => 'en_US'));
        $lang    = $langmap[$_SESSION['language']] ?: $langmap['*'];

        return str_replace('%l', $lang, $path);
    }
}

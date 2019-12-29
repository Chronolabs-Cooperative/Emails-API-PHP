<?php

/**
 * Sample plugin that adds a new tab to the settings section
 * to display some information about the current user
 */
class userinfo extends chronomail_plugin
{
    public $task    = 'settings';
    public $noajax  = true;
    public $noframe = true;

    function init()
    {
        $this->add_texts('localization/', array('userinfo'));
        $this->add_hook('settings_actions', array($this, 'settings_actions'));
        $this->register_action('plugin.userinfo', array($this, 'infostep'));
    }

    function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.userinfo',
            'class'  => 'userinfo',
            'label'  => 'userinfo',
            'domain' => 'userinfo',
        );

        return $args;
    }

    function infostep()
    {
        $this->register_handler('plugin.body', array($this, 'infohtml'));

        $chronomail = chronomail::get_instance();
        $chronomail->output->set_pagetitle($this->gettext('userinfo'));
        $chronomail->output->send('plugin');
    }

    function infohtml()
    {
        $chronomail   = chronomail::get_instance();
        $user     = $chronomail->user;
        $identity = $user->get_identity();

        $table = new html_table(array('cols' => 2, 'class' => 'propform'));

        $table->add('title', html::label('', 'ID'));
        $table->add('', chronomail::Q($user->ID));

        $table->add('title', html::label('', chronomail::Q($this->gettext('username'))));
        $table->add('', chronomail::Q($user->data['username']));

        $table->add('title', html::label('', chronomail::Q($this->gettext('server'))));
        $table->add('', chronomail::Q($user->data['mail_host']));

        $table->add('title', html::label('', chronomail::Q($this->gettext('created'))));
        $table->add('', chronomail::Q($user->data['created']));

        $table->add('title', html::label('', chronomail::Q($this->gettext('lastlogin'))));
        $table->add('', chronomail::Q($user->data['last_login']));

        $table->add('title', html::label('', chronomail::Q($this->gettext('defaultidentity'))));
        $table->add('', chronomail::Q($identity['name'] . ' <' . $identity['email'] . '>'));

        $legend = chronomail::Q('Infos for ' . $user->get_username());
        $out    = html::tag('fieldset', '', html::tag('legend', '', $legend) . $table->show());

        return html::div(array('class' => 'box formcontent'),
            html::div(array('class' => 'boxtitle'), $this->gettext('userinfo'))
            . html::div(array('class' => 'boxcontent'), $out));
    }
}

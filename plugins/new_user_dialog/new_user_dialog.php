<?php

/**
 * Present identities settings dialog to new users
 *
 * When a new user is created, this plugin checks the default identity
 * and sets a session flag in case it is incomplete. An overlay box will appear
 * on the screen until the user has reviewed/completed his identity.
 *
 * @license GNU GPLv3+
 * @author Thomas Bruederli
 * @author Aleksander Machniak
 */
class new_user_dialog extends chronomail_plugin
{
    public $task    = '';
    public $noframe = true;


    function init()
    {
        $this->add_hook('identity_create', array($this, 'create_identity'));
        $this->add_hook('render_page', array($this, 'render_page'));
        $this->register_action('plugin.newusersave', array($this, 'save_data'));
    }

    /**
     * Check newly created identity at first login
     */
    function create_identity($p)
    {
        // set session flag when a new user was created and the default identity seems to be incomplete
        if ($p['login'] && !$p['complete']) {
            $chronomail = chronomail::get_instance();
            $chronomail->user->save_prefs(array('newuserdialog' => true));
        }
    }

    /**
     * Callback function when HTML page is rendered
     * We'll add an overlay box here.
     */
    function render_page($p)
    {
        $chronomail = chronomail::get_instance();
        if ($p['template'] != 'login' && $chronomail->config->get('newuserdialog')) {
            $this->add_texts('localization');

            $identity         = $chronomail->user->get_identity();
            $identities_level = intval($chronomail->config->get('identities_level', 0));

            // compose user-identity dialog
            $table = new html_table(array('cols' => 2, 'class' => 'propform'));

            $table->add('title', html::label('newuserdialog-name', $this->gettext('name')));
            $table->add(null, html::tag('input', array(
                    'id'       => 'newuserdialog-name',
                    'type'     => 'text',
                    'name'     => '_name',
                    'value'    => $identity['name'],
                    'disabled' => $identities_level == 4
            )));

            $table->add('title', html::label('newuserdialog-email', $this->gettext('email')));
            $table->add(null, html::tag('input', array(
                    'id'       => 'newuserdialog-email',
                    'type'     => 'text',
                    'name'     => '_email',
                    'value'    => chronomail_utils::idn_to_utf8($identity['email']),
                    'disabled' => in_array($identities_level, array(1, 3, 4))
            )));

            $table->add('title', html::label('newuserdialog-org', $this->gettext('organization')));
            $table->add(null, html::tag('input', array(
                    'id'       => 'newuserdialog-org',
                    'type'     => 'text',
                    'name'     => '_organization',
                    'value'    => $identity['organization'],
                    'disabled' => $identities_level == 4
            )));

            $table->add('title', html::label('newuserdialog-sig', $this->gettext('signature')));
            $table->add(null, html::tag('textarea', array(
                    'id'   => 'newuserdialog-sig',
                    'name' => '_signature',
                    'rows' => '5',
                ),
                $identity['signature']
            ));

            // add overlay input box to html page
            $chronomail->output->add_footer(html::tag('form', array(
                    'id'     => 'newuserdialog',
                    'action' => $chronomail->url('plugin.newusersave'),
                    'method' => 'post',
                    'class'  => 'formcontent',
                    'style'  => 'display: none',
                ),
                html::p('hint', chronomail::Q($this->gettext('identitydialoghint'))) . $table->show()
            ));

            $title  = chronomail::JQ($this->gettext('identitydialogtitle'));
            $script = "
var newuserdialog = chronomail.show_popup_dialog($('#newuserdialog'), '$title', [{
    text: chronomail.get_label('save'),
    'class': 'mainaction save',
    click: function() {
      var request = {};
      $.each($('form', this).serializeArray(), function() {
        request[this.name] = this.value;
      });

      chronomail.http_post('plugin.newusersave', request, true);
      return false;
    }
  }],
  {
    resizable: false,
    closeOnEscape: false,
    width: 500,
    open: function() { $('#newuserdialog').show(); $('#newuserdialog-name').focus(); },
    beforeClose: function() { return false; }
  }
);
chronomail_webmail.prototype.new_user_dialog_close = function() { newuserdialog.dialog('destroy'); };
";
            // disable keyboard events for messages list (#1486726)
            $chronomail->output->add_script($script, 'docready');
        }
    }

    /**
     * Handler for submitted form (ajax request)
     *
     * Check fields and save to default identity if valid.
     * Afterwards the session flag is removed and we're done.
     */
    function save_data()
    {
        $chronomail      = chronomail::get_instance();
        $identity    = $chronomail->user->get_identity();
        $ident_level = intval($chronomail->config->get('identities_level', 0));
        $disabled    = array();

        $save_data = array(
            'name'         => chronomail_utils::get_input_value('_name', chronomail_utils::INPUT_POST),
            'email'        => chronomail_utils::get_input_value('_email', chronomail_utils::INPUT_POST),
            'organization' => chronomail_utils::get_input_value('_organization', chronomail_utils::INPUT_POST),
            'signature'    => chronomail_utils::get_input_value('_signature', chronomail_utils::INPUT_POST),
        );

        if ($ident_level == 4) {
            $disabled = array('name', 'email', 'organization');
        }
        else if (in_array($ident_level, array(1, 3))) {
            $disabled = array('email');
        }

        foreach ($disabled as $key) {
            $save_data[$key] = $identity[$key];
        }

        if (empty($save_data['name']) || empty($save_data['email'])) {
            $chronomail->output->show_message('formincomplete', 'error');
        }
        else if (!chronomail_utils::check_email($save_data['email'] = chronomail_utils::idn_to_ascii($save_data['email']))) {
            $chronomail->output->show_message('emailformaterror', 'error', array('email' => $save_data['email']));
        }
        else {
            // save data
            $chronomail->user->update_identity($identity['identity_id'], $save_data);
            $chronomail->user->save_prefs(array('newuserdialog' => null));
            // hide dialog
            $chronomail->output->command('new_user_dialog_close');
            $chronomail->output->show_message('successfullysaved', 'confirmation');
        }

        $chronomail->output->send();
    }
}

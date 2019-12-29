/**
 * Enigma plugin script
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
 *
 * Copyright (c) The Chronomail Dev Team
 *
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */

window.chronomail && chronomail.addEventListener('init', function(evt) {
    if (chronomail.env.task == 'settings') {
        if (chronomail.gui_objects.keyslist) {
            chronomail.keys_list = new chronomail_list_widget(chronomail.gui_objects.keyslist,
                {multiselect:true, draggable:false, keyboard:true});
            chronomail.keys_list
                .addEventListener('select', function(o) { chronomail.enigma_keylist_select(o); })
                .addEventListener('keypress', function(o) { chronomail.list_keypress(o, {del: 'plugin.enigma-key-delete'}); })
                .init()
                .focus();

            chronomail.enigma_list();

            chronomail.register_command('firstpage', function(props) { return chronomail.enigma_list_page('first'); });
            chronomail.register_command('previouspage', function(props) { return chronomail.enigma_list_page('previous'); });
            chronomail.register_command('nextpage', function(props) { return chronomail.enigma_list_page('next'); });
            chronomail.register_command('lastpage', function(props) { return chronomail.enigma_list_page('last'); });
        }

        if (chronomail.env.action == 'plugin.enigmakeys') {
            chronomail.register_command('search', function(props) {return chronomail.enigma_search(props); }, true);
            chronomail.register_command('reset-search', function(props) {return chronomail.enigma_search_reset(props); }, true);
            chronomail.register_command('plugin.enigma-import', function() { chronomail.enigma_import(); }, true);
            chronomail.register_command('plugin.enigma-import-search', function() { chronomail.enigma_import_search(); }, true);
            chronomail.register_command('plugin.enigma-key-export', function() { chronomail.enigma_export(); });
            chronomail.register_command('plugin.enigma-key-export-selected', function() { chronomail.enigma_export(true); });
            chronomail.register_command('plugin.enigma-key-import', function() { chronomail.enigma_key_import(); }, true);
            chronomail.register_command('plugin.enigma-key-import-search', function() { chronomail.enigma_key_import_search(); }, true);
            chronomail.register_command('plugin.enigma-key-delete', function(props) { return chronomail.enigma_delete(); });
            chronomail.register_command('plugin.enigma-key-create', function(props) { return chronomail.enigma_key_create(); }, true);
            chronomail.register_command('plugin.enigma-key-save', function(props) { return chronomail.enigma_key_create_save(); }, true);

            chronomail.addEventListener('responseafterplugin.enigmakeys', function() {
                chronomail.enable_command('plugin.enigma-key-export', chronomail.env.rowcount > 0);
                chronomail.triggerEvent('listupdate', {list: chronomail.keys_list, rowcount: chronomail.env.rowcount});
            });

            if (chronomail.gui_objects.importform) {
                // make sure Enter key in search input starts searching
                // instead of submitting the form
                $('#rcmimportsearch').keydown(function(e) {
                    if (e.which == 13) {
                        chronomail.enigma_import_search();
                        return false;
                    }
                });
            }
        }
    }
    else if (chronomail.env.task == 'mail') {
        if (chronomail.env.action == 'compose') {
            chronomail.addEventListener('beforesend', function(props) { chronomail.enigma_beforesend_handler(props); })
                .addEventListener('beforesavedraft', function(props) { chronomail.enigma_beforesavedraft_handler(props); });

            $('#enigmamenu').find('input,label').mouseup(function(e) {
                // don't close the menu on mouse click inside
                e.stopPropagation();
            });

            $('a.button.enigma').prop('tabindex', $('#messagetoolbar > a').first().prop('tabindex'));

            $.each(['encrypt', 'sign'], function() {
                var opt = this, input = $('#enigma' + opt + 'opt');

                if (chronomail.env['enigma_force_' + opt]) {
                    input.prop('checked', true);
                }

                // Compose status bar in Elastic
                if (window.UI && UI.compose_status) {
                    input.on('change', function() { UI.compose_status(opt, this.checked); });
                }

                // As the options might have been initially enabled we have to
                // trigger onchange event, so all handlers can update the state
                input.trigger('change');
            });
        }

        if (chronomail.env.enigma_password_request) {
            chronomail.enigma_password_request(chronomail.env.enigma_password_request);
        }
    }
});


/*********************************************************/
/*********    Enigma Settings/Keys/Certs UI      *********/
/*********************************************************/

// Display key(s) import form
chronomail_webmail.prototype.enigma_key_import = function()
{
    var dialog = $('<iframe>').attr('src', this.url('plugin.enigmakeys', {_a: 'import', _framed: 1})),
        import_func = function(e) {
            var win = dialog[0].contentWindow;
            win.chronomail.enigma_import();
        };

    this.enigma_import_dialog = this.simple_dialog(dialog, this.gettext('enigma.importkeys'), import_func, {
        button: 'import',
        width: 500,
        height: 150
    });
};

// Display key(s) search/import form
chronomail_webmail.prototype.enigma_key_import_search = function()
{
    var dialog = $('<iframe>').attr('src', this.url('plugin.enigmakeys', {_a: 'import-search', _framed: 1})),
        search_func = function() {
            var win = dialog[0].contentWindow;
            win.chronomail.enigma_import_search();
        };

    this.enigma_import_dialog = this.simple_dialog(dialog, this.gettext('enigma.keyimportsearchlabel'), search_func, {
        button: 'search',
        width: 500,
        height: 150
    });
};

chronomail_webmail.prototype.enigma_import_success = function()
{
    var dialog = this.enigma_import_dialog || parent.chronomail.enigma_import_dialog;
    dialog.dialog('destroy');
};

// Display key(s) generation form
chronomail_webmail.prototype.enigma_key_create = function()
{
    this.enigma_loadframe('&_action=plugin.enigmakeys&_a=create&_nav=hide');
};

// Generate key(s) and submit them
chronomail_webmail.prototype.enigma_key_create_save = function()
{
    var options, lock, users = [],
        password = $('#key-pass').val(),
        confirm = $('#key-pass-confirm').val(),
        size = $('#key-size').val();

    $('[name="identity[]"]:checked').each(function() {
        users.push({name: $(this).data('name') || '', email: $(this).data('email')});
    });

    // validate the form
    if (!password || !confirm) {
        this.alert_dialog(this.get_label('enigma.formerror'));
        return;
    }

    if (password != confirm) {
        this.alert_dialog(this.get_label('enigma.passwordsdiffer'));
        return;
    }

    if (!users.length) {
        this.alert_dialog(this.get_label('enigma.noidentselected'));
        return;
    }

    // generate keys
    // use OpenPGP.js if browser supports required features
    if (window.openpgp && (window.msCrypto || (window.crypto && (window.crypto.getRandomValues || window.crypto.subtle)))) {
        lock = this.set_busy(true, 'enigma.keygenerating');
        options = {
            numBits: size,
            userIds: users,
            passphrase: password
        };

        openpgp.generateKey(options).then(function(keypair) {
            // success
            var post = {_a: 'import', _keys: keypair.privateKeyArmored, _generated: 1,
                _passwd: password, _keyid: keypair.key.primaryKey.getFingerprint()};

            // send request to server
            chronomail.http_post('plugin.enigmakeys', post, lock);
        }, function(error) {
            // failure
            console.error(error);
            chronomail.set_busy(false, null, lock);
            chronomail.display_message(chronomail.get_label('enigma.keygenerateerror'), 'error');
        });
    }
    else {
        chronomail.display_message(chronomail.get_label('enigma.keygennosupport'), 'error');
    }
};

// Action executed after successful key generation and import
chronomail_webmail.prototype.enigma_key_create_success = function()
{
    parent.chronomail.enigma_list(1);
};

// Delete key(s)
chronomail_webmail.prototype.enigma_delete = function()
{
    var keys = this.keys_list.get_selection();

    if (!keys.length)
        return;

    this.confirm_dialog(this.get_label('enigma.keyremoveconfirm'), 'delete', function(e, ref) {
        var lock = ref.display_message(ref.get_label('enigma.keyremoving'), 'loading'),
            post = {_a: 'delete', _keys: keys};

        // send request to server
        ref.http_post('plugin.enigmakeys', post, lock);
    });
};

// Export key(s)
chronomail_webmail.prototype.enigma_export = function(selected)
{
    var priv = false,
        list = this.keys_list,
        keys = selected ? list.get_selection().join(',') : '*',
        args = {_keys: keys};

    if (!keys.length)
        return;

    // find out whether selected keys are private
    if (keys == '*')
        priv = true;
    else
        $.each(list.get_selection(), function() {
            flags = $(list.rows[this].obj).data('flags');
            if (flags && flags.indexOf('p') >= 0) {
                priv = true;
                return false;
            }
        });

    // ask the user about including private key in the export
    if (priv)
        return this.show_popup_dialog(
            this.get_label('enigma.keyexportprompt'),
            this.get_label('enigma.exportkeys'),
            [{
                'class': 'export mainaction',
                text: this.get_label('enigma.onlypubkeys'),
                click: function(e) {
                    chronomail.enigma_export_submit(args);
                    $(this).remove();
                }
            },
            {
                'class': 'export',
                text: this.get_label('enigma.withprivkeys'),
                click: function(e) {
                    args._priv = 1;
                    chronomail.enigma_export_submit(args);
                    $(this).remove();
                }
            },
            {
                'class': 'cancel',
                text: this.get_label('close'),
                click: function(e) {
                    $(this).remove();
                }
            }],
            {width: 500}
        );

    this.enigma_export_submit(args);
};

// Sumbitting request for key(s) export
// Done this way to handle password input
chronomail_webmail.prototype.enigma_export_submit = function(data)
{
    var id = 'keyexport-' + new Date().getTime(),
        form = $('<form>').attr({target: id, method: 'post', style: 'display:none',
            action: '?_action=plugin.enigmakeys&_task=settings&_a=export'}),
        iframe = $('<iframe>').attr({name: id, style: 'display:none'})

    form.append($('<input>').attr({name: '_token', value: this.env.request_token}));
    $.each(data, function(i, v) {
        form.append($('<input>').attr({name: i, value: v}));
    });

    iframe.appendTo(document.body);
    form.appendTo(document.body).submit();
};

// Submit key(s) import form
chronomail_webmail.prototype.enigma_import = function()
{
    var form, file, lock,
        id = 'keyimport-' + new Date().getTime();

    if (form = this.gui_objects.importform) {
        file = document.getElementById('rcmimportfile');
        if (file && !file.value) {
            this.alert_dialog(this.get_label('selectimportfile'));
            return;
        }

        lock = this.set_busy(true, 'importwait');
        $('<iframe>').attr({name: id, style: 'display:none'}).appendTo(document.body);
        $(form).attr({target: id, action: this.add_url(form.action, '_unlock', lock)}).submit();

        return true;
    }
};

// Ssearch for key(s) for import
chronomail_webmail.prototype.enigma_import_search = function()
{
    var form, search;

    if (form = this.gui_objects.importform) {
        search = $('#rcmimportsearch').val();
        if (!search) {
            return;
        }

        this.enigma_find_publickey(search);
    }
};

// list row selection handler
chronomail_webmail.prototype.enigma_keylist_select = function(list)
{
    var id = list.get_single_selection(), url;

    if (id)
        url = '&_action=plugin.enigmakeys&_a=info&_id=' + id;

    this.enigma_loadframe(url);
    this.enable_command('plugin.enigma-key-delete', 'plugin.enigma-key-export-selected', list.get_selection().length > 0);
};

// load key frame
chronomail_webmail.prototype.enigma_loadframe = function(url)
{
    var win;

    if (win = this.get_frame_window(this.env.contentframe)) {
        if (!url) {
            if (win.location && win.location.href.indexOf(this.env.blankpage) < 0)
                win.location.href = this.env.blankpage;
            if (this.env.frame_lock)
                this.set_busy(false, null, this.env.frame_lock);
            return;
        }

        this.env.frame_lock = this.set_busy(true, 'loading');
        win.location.href = this.env.comm_path + '&_framed=1' + url;
    }
};

// Search keys/certs
chronomail_webmail.prototype.enigma_search = function(props)
{
    if (!props && this.gui_objects.qsearchbox)
        props = this.gui_objects.qsearchbox.value;

    if (props || this.env.search_request) {
        var params = {'_a': 'search', '_q': props},
          lock = this.set_busy(true, 'searching');
//        if (this.gui_objects.search_filter)
//          addurl += '&_filter=' + this.gui_objects.search_filter.value;
        this.env.current_page = 1;
        this.enigma_loadframe();
        this.enigma_clear_list();
        this.http_post('plugin.enigmakeys', params, lock);
    }

    return false;
};

// Reset search filter and the list
chronomail_webmail.prototype.enigma_search_reset = function(props)
{
    var s = this.env.search_request;
    this.reset_qsearch();

    if (s) {
        this.enigma_loadframe();
        this.enigma_clear_list();

        // refresh the list
        this.enigma_list();
    }

    return false;
};

// Keys/certs listing
chronomail_webmail.prototype.enigma_list = function(page, reset_frame)
{
    if (this.is_framed())
        return parent.chronomail.enigma_list(page, reset_frame);

    var params = {'_a': 'list'},
      lock = this.set_busy(true, 'loading');

    this.env.current_page = page ? page : 1;

    if (this.env.search_request)
        params._q = this.env.search_request;
    if (page)
        params._p = page;

    this.enigma_clear_list(reset_frame);
    this.http_post('plugin.enigmakeys', params, lock);
};

// Change list page
chronomail_webmail.prototype.enigma_list_page = function(page)
{
    if (page == 'next')
        page = this.env.current_page + 1;
    else if (page == 'last')
        page = this.env.pagecount;
    else if (page == 'prev' && this.env.current_page > 1)
        page = this.env.current_page - 1;
    else if (page == 'first' && this.env.current_page > 1)
        page = 1;

    this.enigma_list(page);
};

// Remove list rows
chronomail_webmail.prototype.enigma_clear_list = function(reset_frame)
{
    if (reset_frame !== false)
        this.enigma_loadframe();

    if (this.keys_list)
        this.keys_list.clear(true);

    this.enable_command('plugin.enigma-key-delete', 'plugin.enigma-key-delete-selected', false);
    this.triggerEvent('listupdate', {list: this.keys_list, rowcount: this.keys_list.rowcount});
};

// Adds a row to the list
chronomail_webmail.prototype.enigma_add_list_row = function(r)
{
    if (!this.gui_objects.keyslist || !this.keys_list)
        return false;

    var list = this.keys_list,
        tbody = this.gui_objects.keyslist.tBodies[0],
        rowcount = tbody.rows.length,
        even = rowcount%2,
        // for performance use DOM instead of jQuery here
        row = document.createElement('tr'),
        col = document.createElement('td');

    row.id = 'rcmrow' + r.id;
    row.className = 'message';
    if (r.flags) $(row).data('flags', r.flags);

    col.className = 'name';
    col.innerHTML = r.name;
    row.appendChild(col);
    list.insert_row(row);
};


/*********************************************************/
/*********        Enigma Message methods         *********/
/*********************************************************/

// handle message send/save action
chronomail_webmail.prototype.enigma_beforesend_handler = function(props)
{
    this.env.last_action = 'send';
    this.enigma_compose_handler(props);
};

chronomail_webmail.prototype.enigma_beforesavedraft_handler = function(props)
{
    this.env.last_action = 'savedraft';
    this.enigma_compose_handler(props);
};

chronomail_webmail.prototype.enigma_compose_handler = function(props)
{
    var form = this.gui_objects.messageform;

    // copy inputs from enigma menu to the form (not used in Elastic)
    $('#enigmamenu input').each(function() {
        var id = this.id + '_cpy', input = $('#' + id);

        if (!input.length) {
            input = $(this).clone();
            input.prop({id: id, type: 'hidden'}).appendTo(form);
        }

        input.val(this.checked ? '1' : '');
    });
};

// Import attached keys/certs file
chronomail_webmail.prototype.enigma_import_attachment = function(mime_id)
{
    var lock = this.set_busy(true, 'loading'),
        post = {_uid: this.env.uid, _mbox: this.env.mailbox, _part: mime_id};

    this.http_post('plugin.enigmaimport', post, lock);

    return false;
};

// password request popup
chronomail_webmail.prototype.enigma_password_request = function(data)
{
    if (!data || !data.keyid) {
        return;
    }

    var ref = this,
        msg = this.get_label('enigma.enterkeypass'),
        myprompt = $('<div class="prompt">'),
        myprompt_content = $('<p class="message">')
            .appendTo(myprompt),
        myprompt_input = $('<input>').attr({type: 'password', size: 30})
            .keypress(function(e) {
                if (e.which == 13)
                    (ref.is_framed() ? window.parent.$ : $)('.ui-dialog-buttonpane button.mainaction:visible').click();
            })
            .appendTo(myprompt);

    data.key = data.keyid;
    if (data.keyid.length > 8)
        data.keyid = data.keyid.substr(data.keyid.length - 8);

    $.each(['keyid', 'user'], function() {
        msg = msg.replace('$' + this, data[this]);
    });

    myprompt_content.text(msg);

    this.show_popup_dialog(myprompt, this.get_label('enigma.enterkeypasstitle'),
        [{
            text: this.get_label('ok'),
            'class': 'mainaction save unlock',
            click: function(e) {
                e.stopPropagation();

                var jq = ref.is_framed() ? window.parent.$ : $;

                data.password = myprompt_input.val();

                if (!data.password) {
                    myprompt_input.focus();
                    return;
                }

                ref.enigma_password_submit(data);
                jq(this).remove();
            }
        },
        {
            text: this.get_label('cancel'),
            'class': 'cancel',
            click: function(e) {
                var jq = ref.is_framed() ? window.parent.$ : $;
                e.stopPropagation();
                jq(this).remove();
            }
        }], {width: 400});

    if (this.is_framed() && parent.chronomail.message_list) {
        // this fixes bug when pressing Enter on "Save" button in the dialog
        parent.chronomail.message_list.blur();
    }
};

// submit entered password
chronomail_webmail.prototype.enigma_password_submit = function(data)
{
    var lock, form;

    if (this.env.action == 'compose' && !data['compose-init']) {
        return this.enigma_password_compose_submit(data);
    }
    else if (this.env.action == 'plugin.enigmakeys' && (form = this.gui_objects.importform)) {
        if (!$('input[name="_keyid"]', form).length) {
            $(form).append($('<input>').attr({type: 'hidden', name: '_keyid', value: data.key}))
                .append($('<input>').attr({type: 'hidden', name: '_passwd', value: data.password}))
        }

        return this.enigma_import();
    }

    lock = data.nolock ? null : this.set_busy(true, 'loading');
    form = $('<form>')
        .attr({method: 'post', action: data.action || location.href, style: 'display:none'})
        .append($('<input>').attr({type: 'hidden', name: '_keyid', value: data.key}))
        .append($('<input>').attr({type: 'hidden', name: '_passwd', value: data.password}))
        .append($('<input>').attr({type: 'hidden', name: '_token', value: this.env.request_token}))
        .append($('<input>').attr({type: 'hidden', name: '_unlock', value: lock}));

    // Additional form fields for request parameters
    $.each(data, function(i, v) {
        if (i.indexOf('input') == 0)
            form.append($('<input>').attr({type: 'hidden', name: i.substring(5), value: v}))
    });

    if (data.iframe) {
        var name = 'enigma_frame_' + (new Date()).getTime(),
            frame = $('<iframe>').attr({style: 'display:none', name: name}).appendTo(document.body);
        form.attr('target', name);
    }

    form.appendTo(document.body).submit();
};

// submit entered password - in mail compose page
chronomail_webmail.prototype.enigma_password_compose_submit = function(data)
{
    var form = this.gui_objects.messageform;

    if (!$('input[name="_keyid"]', form).length) {
        $(form).append($('<input>').attr({type: 'hidden', name: '_keyid', value: data.key}))
            .append($('<input>').attr({type: 'hidden', name: '_passwd', value: data.password}));
    }
    else {
        $('input[name="_keyid"]', form).val(data.key);
        $('input[name="_passwd"]', form).val(data.password);
    }

    this.submit_messageform(this.env.last_action == 'savedraft');
};

// Display no-key error with key search button
chronomail_webmail.prototype.enigma_key_not_found = function(data)
{
    var params = {width: 500, dialogClass: 'error'},
        buttons = [{
            'class': 'mainaction search',
            text: data.button,
            click: function() {
                $(this).remove();
                chronomail.enigma_find_publickey(data.email);
            }
        }];

    if (data.mode == 'encrypt') {
        buttons.push({
            'class': 'send',
            text: chronomail.get_label('enigma.sendunencrypted'),
            click: function(e) {
                $(this).remove();
                $('#enigmaencryptopt').prop('checked', false);
                chronomail.command('send', {nocheck: true}, e.target, e.originalEvent);
            }
        });
    }

    buttons.push({
        'class': 'cancel',
        text: this.get_label('cancel'),
        click: function() {
            $(this).remove();
        }
    });

    return this.show_popup_dialog(data.text, data.title, buttons, params);
};

// Search for a public key on the key server
chronomail_webmail.prototype.enigma_find_publickey = function(email)
{
    this.mailvelope_search_pubkeys([email],
        function(status) {},
        function(key) {
            var lock = chronomail.set_busy(true, 'enigma.importwait'),
                post = {_a: 'import', _keys: key};

            if (chronomail.env.action == 'plugin.enigmakeys')
                post._refresh = 1;

            // send request to server
            chronomail.http_post('plugin.enigmakeys', post, lock);
        }
    );
};

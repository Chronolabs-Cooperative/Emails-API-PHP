/**
 * Mark-as-Junk plugin script
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
 *
 * Copyright (c) The Chronomail Dev Team
 * Copyright (C) Philip Weir
 *
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */

chronomail_webmail.prototype.markasjunk_mark = function(is_spam) {
    var uids = this.env.uid ? [this.env.uid] : this.message_list.get_selection();
    if (!uids)
        return;

    var lock = this.set_busy(true, 'loading');
    this.http_post('plugin.markasjunk.' + (is_spam ? 'junk' : 'not_junk'), this.selection_post_data({_uid: uids}), lock);
}

chronomail_webmail.prototype.markasjunk_move = function(mbox, uids) {
    var prev_uid = this.env.uid;

    if (this.message_list && uids.length == 1 && !this.message_list.in_selection(uids[0]))
        this.env.uid = uids[0];

    if (mbox)
        this.move_messages(mbox);
    else if (this.env.markasjunk_permanently_remove == true)
        this.permanently_remove_messages();
    else
        this.delete_messages();

    this.env.uid = prev_uid;
}

chronomail_webmail.prototype.markasjunk_toggle_button = function() {
    var spamobj = $('a.junk'),
        hamobj = $('a.notjunk'),
        disp = {spam: true, ham: true};

    if (this.env.markasjunk_spam_only) {
        disp.ham = false;
    }
    else if (!this.is_multifolder_listing() && this.env.markasjunk_spam_mailbox) {
        if (this.env.mailbox != this.env.markasjunk_spam_mailbox)
            disp.ham = false;
        else
            disp.spam = false;
    }

    // if only 1 button is visible make sure its the last one (for styling)
    // allow for multiple instances of the buttons, eg toolbar and contextmenu
    $.each(spamobj, function(i) {
        var cur_spamobj = spamobj.eq(i),
            cur_hamobj = hamobj.eq(i),
            cur_index = spamobj.eq(i).index();

        if (cur_spamobj.parent('li').length > 0) {
            cur_spamobj = cur_spamobj.parent();
            cur_hamobj = cur_hamobj.parent();
        }

        var evt_rtn = chronomail.triggerEvent('markasjunk-update', {objs: {spamobj: cur_spamobj, hamobj: cur_hamobj}, disp: disp});
        if (evt_rtn && evt_rtn.abort)
            return;

        disp = evt_rtn ? evt_rtn.disp : disp;

        disp.spam ? cur_spamobj.show() : cur_spamobj.hide();
        disp.ham ? cur_hamobj.show() : cur_hamobj.hide();

        if (disp.spam && !disp.ham) {
            if (cur_index < cur_hamobj.index()) {
                cur_spamobj.insertAfter(cur_hamobj);
            }
        }
        else if (cur_index > cur_hamobj.index()) {
            cur_hamobj.insertAfter(cur_spamobj);
        }
    });
}

chronomail_webmail.prototype.markasjunk_is_spam_mbox = function() {
    return !this.is_multifolder_listing() && this.env.mailbox == this.env.markasjunk_spam_mailbox;
}

if (window.chronomail) {
    chronomail.addEventListener('init', function() {
        // register command (directly enable in message view mode)
        chronomail.register_command('plugin.markasjunk.junk', function() { chronomail.markasjunk_mark(true); }, !chronomail.markasjunk_is_spam_mbox() && chronomail.env.uid);
        chronomail.register_command('plugin.markasjunk.not_junk', function() { chronomail.markasjunk_mark(false); }, chronomail.env.uid);

        if (chronomail.message_list) {
            chronomail.message_list.addEventListener('select', function(list) {
                chronomail.enable_command('plugin.markasjunk.junk', !chronomail.markasjunk_is_spam_mbox() && list.get_selection(false).length > 0);
                chronomail.enable_command('plugin.markasjunk.not_junk', list.get_selection(false).length > 0);
            });
        }

        // make sure the correct icon is displayed even when there is no listupdate event
        chronomail.markasjunk_toggle_button();
    });

    chronomail.addEventListener('listupdate', function() { chronomail.markasjunk_toggle_button(); });

    chronomail.addEventListener('beforemoveto', function(mbox) {
        if (mbox && typeof mbox === 'object')
            mbox = mbox.id;

        var is_spam = null;

        // check if destination mbox equals junk box (and we're not already in the junk box)
        if (chronomail.env.markasjunk_move_spam && mbox && mbox == chronomail.env.markasjunk_spam_mailbox && mbox != chronomail.env.mailbox)
            is_spam = true;
        // or if destination mbox equals ham box and we are in the junk box
        else if (chronomail.env.markasjunk_move_ham && mbox && mbox == chronomail.env.markasjunk_ham_mailbox && chronomail.env.mailbox == chronomail.env.markasjunk_spam_mailbox)
            is_spam = false;

        if (is_spam !== null) {
            chronomail.markasjunk_mark(is_spam);
            return false;
        }
    });
}

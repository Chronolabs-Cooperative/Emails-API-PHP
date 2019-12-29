/**
 * ZipDownload plugin script
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
    // register additional actions
    chronomail.register_command('download-eml', function() { chronomail_zipdownload('eml'); });
    chronomail.register_command('download-mbox', function() { chronomail_zipdownload('mbox'); });
    chronomail.register_command('download-maildir', function() { chronomail_zipdownload('maildir'); });

    // commands status
    chronomail.message_list && chronomail.message_list.addEventListener('select', function(list) {
        var selected = list.get_selection().length;

        chronomail.enable_command('download', selected > 0);
        chronomail.enable_command('download-eml', selected == 1);
        chronomail.enable_command('download-mbox', 'download-maildir', selected > 1);
    });

    // hook before default download action
    chronomail.addEventListener('beforedownload', chronomail_zipdownload_menu);

    // find and modify default download link/button
    $.each(chronomail.buttons['download'] || [], function() {
        var link = $('#' + this.id),
            span = $('span', link);

        if (!span.length) {
            span = $('<span>');
            link.html('').append(span);
        }

        link.attr('aria-haspopup', 'true');

        span.text(chronomail.get_label('zipdownload.download'));
        chronomail.env.download_link = link;
    });
});


function chronomail_zipdownload(mode)
{
    // default .eml download of single message
    if (mode == 'eml') {
        var uid = chronomail.get_single_uid();
        chronomail.goto_url('viewsource', chronomail.params_from_uid(uid, {_save: 1}), false, true);
        return;
    }

    // multi-message download, use hidden form to POST selection
    if (chronomail.message_list && chronomail.message_list.get_selection().length > 1) {
        var inputs = [],
            post = chronomail.selection_post_data(),
            id = 'zipdownload-' + new Date().getTime(),
            iframe = $('<iframe>').attr({name: id, style: 'display:none'}),
            form = $('<form>').attr({
                    target: id,
                    style: 'display: none',
                    method: 'post',
                    action: '?_task=mail&_action=plugin.zipdownload.messages'
                });

        post._mode = mode;
        post._token = chronomail.env.request_token;

        $.each(post, function(k, v) {
            if (typeof v == 'object' && v.length > 1) {
              for (var j=0; j < v.length; j++)
                  inputs.push($('<input>').attr({type: 'hidden', name: k+'[]', value: v[j]}));
            }
            else {
                inputs.push($('<input>').attr({type: 'hidden', name: k, value: v}));
            }
        });

        iframe.appendTo(document.body);
        form.append(inputs).appendTo(document.body).submit();
    }
}

// display download options menu
function chronomail_zipdownload_menu(e)
{
    // show (sub)menu for download selection
    chronomail.command('menu-open', 'zipdownload-menu', e && e.target ? e.target : chronomail.env.download_link, e);

    // abort default download action
    return false;
}

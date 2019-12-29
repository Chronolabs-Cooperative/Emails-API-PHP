/**
 * Help plugin client script
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

// hook into switch-task event to open the help window
if (window.chronomail) {
    chronomail.addEventListener('beforeswitch-task', function(prop) {
        // catch clicks to help task button
        if (prop == 'help') {
            if (chronomail.task == 'help')  // we're already there
                return false;

            var url = chronomail.url('help/index', { _rel: chronomail.task + (chronomail.env.action ? '/'+chronomail.env.action : '') });
            if (chronomail.env.help_open_extwin) {
                chronomail.open_window(url, 1020, false);
            }
            else {
                chronomail.redirect(url, false);
            }

            return false;
        }
    });

    chronomail.addEventListener('init', function(prop) {
        if (chronomail.env.contentframe && chronomail.task == 'help') {
            $('#' + chronomail.env.contentframe).on('load error', function(e) {
                // Unlock UI
                chronomail.set_busy(false, null, chronomail.env.frame_lock);
                chronomail.env.frame_lock = null;

                // Select menu item
                if (e.type == 'load') {
                    $(chronomail.env.help_action_item).parents('ul').children().removeClass('selected');
                    $(chronomail.env.help_action_item).parent().addClass('selected');
                }
            });

            try {
                var win = chronomail.get_frame_window(chronomail.env.contentframe);
                if (win && win.location.href.indexOf(chronomail.env.blankpage) >= 0) {
                    show_help_content(chronomail.env.action);
                }
            }
            catch (e) { /* ignore */}
        }
    });
}

function show_help_content(action, event)
{
    var win, target = window,
        url = chronomail.env.help_links[action];

    if (win = chronomail.get_frame_window(chronomail.env.contentframe)) {
        target = win;
        url += (url.indexOf('?') > -1 ? '&' : '?') + '_framed=1';
    }

    if (chronomail.env.extwin) {
        url += (url.indexOf('?') > -1 ? '&' : '?') + '_extwin=1';
    }

    if (/^self/.test(url)) {
        url = url.substr(4) + '&_content=1&_task=help&_action=' + action;
    }

    chronomail.env.help_action_item = event ? event.target : $('[rel="' + action + '"]');
    chronomail.show_contentframe(true);
    chronomail.location_href(url, target, true);

    return false;
}

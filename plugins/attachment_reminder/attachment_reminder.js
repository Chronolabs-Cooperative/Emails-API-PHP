/**
 * Attachment Reminder plugin script
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

function chronomail_get_compose_message()
{
  var msg;

  if (window.tinyMCE && (ed = tinyMCE.get(chronomail.env.composebody))) {
    msg = ed.getContent();
    msg = msg.replace(/<blockquote[^>]*>(.|[\r\n])*<\/blockquote>/gmi, '');
  }
  else {
    msg = $('#' + chronomail.env.composebody).val();
    msg = msg.replace(/^>.*$/gmi, '');
  }

  return msg;
};

function chronomail_check_message(msg)
{
  var i, rx, keywords = chronomail.get_label('keywords', 'attachment_reminder').split(",").concat([".doc", ".pdf"]);

  keywords = $.map(keywords, function(n) { return RegExp.escape(n); });
  rx = new RegExp('(' + keywords.join('|') + ')', 'i');

  return msg.search(rx) != -1;
};

function chronomail_have_attachments()
{
  return chronomail.env.attachments && $('li', chronomail.gui_objects.attachmentlist).length;
};

function chronomail_attachment_reminder_dialog()
{
  var buttons = {};

  buttons[chronomail.get_label('addattachment')] = function() {
    $(this).remove();
    if (window.UI && UI.show_uploadform) // Larry skin
      UI.show_uploadform();
    else if (window.chronomail_ui && chronomail_ui.show_popup) // classic skin
      chronomail_ui.show_popup('uploadmenu', true);
  };
  buttons[chronomail.get_label('send')] = function(e) {
    $(this).remove();
    chronomail.env.attachment_reminder = true;
    chronomail.command('send', '', e);
  };

  chronomail.env.attachment_reminder = false;
  chronomail.show_popup_dialog(
    chronomail.get_label('attachment_reminder.forgotattachment'),
    chronomail.get_label('attachment_reminder.missingattachment'),
    buttons,
    {button_classes: ['mainaction attach', 'send']}
  );
};


if (window.chronomail) {
  chronomail.addEventListener('beforesend', function(evt) {
    var msg = chronomail_get_compose_message(),
      subject = $('#compose-subject').val();

    if (!chronomail.env.attachment_reminder && !chronomail_have_attachments()
      && (chronomail_check_message(msg) || chronomail_check_message(subject))
    ) {
      chronomail_attachment_reminder_dialog();
      return false;
    }
  });
}

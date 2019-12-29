/**
 * Password plugin script
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
    if (chronomail.env.password_disabled) {
        $('#password-form input').prop('disabled', true);
        // reload page after ca. 3 minutes
        chronomail.reload(3 * 60 * 1000 - 2000);
        return;
    }

    // register command handler
    chronomail.register_command('plugin.password-save', function() {
        var input_curpasswd = chronomail_find_object('_curpasswd'),
            input_newpasswd = chronomail_find_object('_newpasswd'),
            input_confpasswd = chronomail_find_object('_confpasswd');

      if (input_curpasswd && input_curpasswd.value == '') {
          chronomail.alert_dialog(chronomail.get_label('nocurpassword', 'password'), function() {
              input_curpasswd.focus();
              return true;
            });
      }
      else if (input_newpasswd && input_newpasswd.value == '') {
          chronomail.alert_dialog(chronomail.get_label('nopassword', 'password'), function() {
              input_newpasswd.focus();
              return true;
            });
      }
      else if (input_confpasswd && input_confpasswd.value == '') {
          chronomail.alert_dialog(chronomail.get_label('nopassword', 'password'), function() {
              input_confpasswd.focus();
              return true;
            });
      }
      else if (input_newpasswd && input_confpasswd && input_newpasswd.value != input_confpasswd.value) {
          chronomail.alert_dialog(chronomail.get_label('passwordinconsistency', 'password'), function() {
              input_newpasswd.focus();
              return true;
            });
      }
      else {
          chronomail.gui_objects.passform.submit();
      }
    }, true);

    $('input:not(:hidden)').first().focus();
});

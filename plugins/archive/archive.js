/**
 * Archive plugin script
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

function chronomail_archive(prop)
{
  if (chronomail_is_archive())
    return;

  var post_data = chronomail.selection_post_data();

  // exit if selection is empty
  if (!post_data._uid)
    return;

  // Disable message command buttons until a message is selected
  chronomail.enable_command(chronomail.env.message_commands, false);
  chronomail.enable_command('plugin.archive', false);

  // let the server sort the messages to the according subfolders
  chronomail.with_selected_messages('move', post_data, null, 'plugin.move2archive');

  // Reset preview (must be after with_selected_messages() call)
  chronomail.show_contentframe(false);
}

function chronomail_is_archive()
{
  // check if current folder is an archive folder or one of its children
  return chronomail.env.mailbox == chronomail.env.archive_folder
    || chronomail.env.mailbox.startsWith(chronomail.env.archive_folder + chronomail.env.delimiter);
}

// callback for app-onload event
if (window.chronomail) {
  chronomail.addEventListener('init', function(evt) {
    // register command (directly enable in message view mode)
    chronomail.register_command('plugin.archive', chronomail_archive, chronomail.env.uid && !chronomail_is_archive());

    // add event-listener to message list
    if (chronomail.message_list)
      chronomail.message_list.addEventListener('select', function(list) {
        chronomail.enable_command('plugin.archive', list.get_selection().length > 0 && !chronomail_is_archive());
      });

    // set css style for archive folder
    var li;
    if (chronomail.env.archive_folder) {
      // in Settings > Folders
      if (chronomail.subscription_list)
        li = chronomail.subscription_list.get_item(chronomail.env.archive_folder);
      // in folders list
      else
        li = chronomail.get_folder_li(chronomail.env.archive_folder, '', true);

      if (li)
        $(li).addClass('archive');

      // in folder selector popup
      chronomail.addEventListener('menu-open', function(p) {
        if (p.name == 'folder-selector') {
          var search = chronomail.env.archive_folder;
          $('a', p.obj).filter(function() { return $(this).data('id') == search; }).parent().addClass('archive');
        }
      });
    }
  });
}

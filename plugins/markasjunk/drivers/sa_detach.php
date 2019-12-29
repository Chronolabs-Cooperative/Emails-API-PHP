<?php

/**
 * SpamAssassin detach ham driver
 *
 * @version 2.0
 *
 * @author Philip Weir
 *
 * Copyright (C) 2011-2014 Philip Weir
 *
 * This driver is part of the MarkASJunk plugin for Chronomail.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Chronomail. If not, see https://www.gnu.org/licenses/.
 */
class markasjunk_sa_detach
{
    public function spam($uids, $src_mbox, $dst_mbox)
    {
        // do nothing
    }

    public function ham(&$uids, $src_mbox, $dst_mbox)
    {
        $chronomail    = chronomail::get_instance();
        $storage  = $chronomail->storage;
        $new_uids = array();

        foreach ($uids as $uid) {
            $saved   = false;
            $message = new chronomail_message($uid);

            if (count($message->attachments) > 0) {
                foreach ($message->attachments as $part) {
                    if ($part->ctype_primary == 'message' && $part->ctype_secondary == 'rfc822' && $part->ctype_parameters['x-spam-type'] == 'original') {
                        $orig_message_raw = $message->get_part_body($part->mime_id);

                        if ($saved = $storage->save_message($dst_mbox, $orig_message_raw)) {
                            $chronomail->output->command('markasjunk_move', null, array($uid));
                            array_push($new_uids, $saved);
                        }
                    }
                }
            }
        }

        if (count($new_uids) > 0) {
            $uids = $new_uids;
        }
    }
}

<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// The following two are what gets put into notification email digests
//
require_once("../inc/util.inc");

function friend_notify_req_email_line($notify) {
    $src_user = BoincUser::lookup_id($notify->opaque);
    if (!$src_user) return null;
    return get_user_random_name($src_user->name)." has requested friendship with you. Please accept or decline.";
}

function friend_notify_accept_email_line($notify) {
    $src_user = BoincUser::lookup_id($notify->opaque);
    if (!$src_user) return null;
    return get_user_random_name($src_user->name)." has confirmed you as a friend";
}

// The following two are what gets put in the Notification
// area of user's Account page
//
function friend_notify_req_web_line($notify) {
    $user = BoincUser::lookup_id($notify->opaque);
    if (!$user) return null;
    return "
        <a href=friend.php?action=query&userid=$notify->opaque>Friendship request</a> from <a href=show_user.php?userid=$user->id>get_user_random_name($user->name)</a>
    ";
}

function friend_notify_accept_web_line($notify) {
    $user = BoincUser::lookup_id($notify->opaque);
    if (!$user) return null;
    return "
        <a href=friend.php?action=accepted&userid=$notify->opaque>Friendship confirmation</a> from get_user_random_name($user->name)
    ";
}

function send_friend_request_email($src_user, $dest_user, $msg) {
    $message  = 
get_user_random_name($src_user->name)." has added you as a friend at ".PROJECT.".
";
    if (strlen($msg)) {
        $message .= 
get_user_random_name($src_user->name)." says: $msg
";
    }

    $message .= "
Please accept or decline by visiting
".secure_url_base().USER_HOME."

--------------------------
To change email preferences, visit:
".secure_url_base()."edit_forum_preferences_form.php
Do not reply to this message.
" ;
    send_email($dest_user, "[".PROJECT."] friend request", $message);
}

function send_friend_accept_email($dest_user, $src_user, $msg) {
    $message  = 
get_user_random_name($dest_user->name)." has confirmed you as a friend at ".PROJECT.".
";
    if (strlen($msg)) {
        $message .= 
get_user_random_name($dest_user->name)." says: $msg
";
    }

    $message .= "
Visit your Account page at
".secure_url_base().USER_HOME."

--------------------------
To change email preferences, visit:
".secure_url_base()."edit_forum_preferences_form.php
Do not reply to this message.
" ;
    send_email($src_user, "[".PROJECT."] friend confirmed", $message);
}

function friend_req_rss($notify, &$title, &$msg, &$url) {
    $src_user = BoincUser::lookup_id($notify->opaque);
    if (!$src_user) {
        $msg = null;
        return;
    }
    $title = "Friend request";
    $msg = get_user_random_name($src_user->name)." has requested friendship with you. Please accept or decline.";
    $url = secure_url_base()."friend.php?action=query&target_userid=$notify->userid&userid=$notify->opaque";
}

function friend_accept_rss($notify, &$title, &$msg, &$url) {
    $src_user = BoincUser::lookup_id($notify->opaque);
    if (!$src_user) {
        $msg = null;
        return;
    }
    $title = "Friendship confirmation";
    $msg = get_user_random_name($src_user->name)." has confirmed you as a friend";
    $url = secure_url_base().USER_HOME;
}

?>

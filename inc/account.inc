<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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

// functions related to account creation and login:
// - forms for create / login
// - function to make login token

// If have recent token, return it.
// Else make login token, store in user record, return token
//
function make_login_token($user) {
    $now = time();
    if ($now - $user->login_token_time < 86400) {
        return $user->login_token;
    }
    $token = substr(random_string(), 0, 8);
    $user->update("login_token='$token', login_token_time=$now");
    return $token;
}

// return HTML string for a checkbox for toggling password visibility
//
function passwd_visible_checkbox($name) {
    return sprintf('
<script>
function toggle_passwd() {
    var c = document.getElementById("passwd_visible");
    var x = document.getElementById("%s");
    if (c.checked) {
        x.type = "text";
    } else {
        x.type = "password";
    }
}
</script>
<input type="checkbox" id="passwd_visible" onclick="toggle_passwd()"> <label for="passwd_visible"><small>Show password</small></label>
        ', $name
    );
}
//function for creating an account
function create_account_form($teamid, $next_url) {
    global $recaptcha_public_key;
    form_input_hidden('next_url', $next_url);

    if ($teamid) {
        form_input_hidden('teamid', $teamid);
    }

    // Using invitation codes to restrict access?
    //
    if (defined('INVITE_CODES')) {
        form_input_text(
            sprintf('<span title="%s">%s</span>',
                tra("An invitation code is required to create an account."),
                tra("Invitation code")
            ),
            "invite_code"
        );
    }
//The code that governs the PHP inputs for account information
//Based on Joshua's tooltip code for Homepage
    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Identifies you on our web site. Use your real name or a nickname."),
            '<!-- attribute href of html tag a was removed by Gerald Joshua --><a data-toggle="tooltip" style="border-bottom: 1px dotted #000;text-decoration: none;" 
            title="The name that will be used to uniquely identify you throughout the system,
website, etc.">'.tra("Screen name").'</a>'
        ),
        "new_name"
    );
    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Must be a valid address of the form 'name@domain'."),
            '<!-- attribute href of html tag a was removed by Gerald Joshua --><a data-toggle="tooltip" style="border-bottom: 1px dotted #000;text-decoration: none;" 
            title="The email address must be submitted in the form of `name@domain` and will
be used to contact you regarding any communications or related to this system,
website, etc.">'.tra("Email address").'</a>'
        ),
        "new_email_addr"
    );
    $min_passwd_length = parse_element(get_config(), "<min_passwd_length>");
    if (!$min_passwd_length) {
        $min_passwd_length = 6;
    }

    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Must be at least %1 characters", $min_passwd_length),
            '<!-- attribute href of html tag a was removed by Gerald Joshua --><a data-toggle="tooltip" style="border-bottom: 1px dotted #000;text-decoration: none;" 
title="Password must have a minimum of 6 characters. Will be used for logging into
this website.">'.tra("Password").'</a>'
        ),
        "passwd", "", "password",'id="passwd"',passwd_visible_checkbox("passwd")
    );
    form_select(
        sprintf('<span title="%s">%s</span>',
            tra("Select the country you want to represent, if any."),
            '<!-- attribute href of html tag a was removed by Gerald Joshua --> <a data-toggle="tooltip" style="border-bottom: 1px dotted #000;text-decoration: none;" 
title="The country from which you will be operating.">'.tra("Country").'</a>'
        ),
        "country",
        country_select_options()
    );
//--==------==------===--------------===-------------------========----------------------
    if (POSTAL_CODE) {
        form_input_text(
            tra("Postal or ZIP Code")."<br><small>".tra("Optional")."</small>",
            "postal_code"
        );
    }


}



//The end of the PHP account inputs code
//----=====------===========-----------------================----------------=====

function login_form($next_url) {
    form_start(secure_url_base()."/login_action.php", "post");
    form_input_hidden("next_url", $next_url);
    if (LDAP_HOST) {
        $x = "Email address or LDAP user name:";
    } else {
        $x = tra("Email address:");
    }
    form_input_text($x, "email_addr");
    form_input_text(
        tra("Password:").'<br><small><a href="get_passwd.php">' . tra("forgot password?") . "</a></small>",
        "passwd",
        "",
        "password",
        'id="passwd"',
        passwd_visible_checkbox("passwd")
    );
    form_checkboxes(tra("Stay logged in"),
        array(array("stay_logged_in", "", false))
    );
    form_submit("Log in");
    form_end();
}

?>
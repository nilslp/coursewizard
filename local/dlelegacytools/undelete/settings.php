<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/config.php' );
require_once( dirname( __FILE__ ) . '/lib.php' );

admin_externalpage_setup( 'undeluserssettings' );

//first thing to do is deal with any incoming undeletes before setting up the page
$do_undelete = optional_param( 'do_undelete', 0, PARAM_INT );
if($do_undelete){
    $undelete_id = required_param('user_id', PARAM_INT);
    $user  = $DB->get_record('user', array('id'=>$undelete_id, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);
    $done = dlt_undelete_user($user);
    if($done === true){
        $alert_text = $user->firstname . " " .$user->lastname.'\nSuccessfully undeleted';
    }else{
        $alert_text = 'Error\n'.$user->firstname . " " .$user->lastname . '\n'.$done;
    }

}
################################################################################


// special case for modern governor, we need to tweak the user filters
if ((int)get_config('local_moderngovernor','synchierarchy')) {
    $fieldnames = array('lastname', 'firstname', 'email', 'city', 'country',
                                'confirmed', 'suspended', 'profile',
                                'firstaccess', 'lastaccess', 'timemodified',
                                'username', 'auth', 'mnethostid');
}

echo $OUTPUT->header();
echo $OUTPUT->heading( get_string( 'undeleteusers', 'local_dlelegacytools' ) );
$perpage = 30;
$page    = optional_param( 'page', 0, PARAM_INT );
$sort    = optional_param( 'sort', 'name', PARAM_TEXT );
$dir     = optional_param( 'dir', 'ASC', PARAM_TEXT );
$PAGE->set_context( context_system::instance() );
$context      = context_system::instance();
$ufiltering   = new user_filtering('','',array('deleted'=>1));

$extracolumns = get_extra_user_fields( $context );
$columns      = array_merge( array(
     'firstname',
    'lastname'
), $extracolumns );
foreach ( $columns as $column ) {
    $string[$column] = get_user_field_name( $column );
    if ( $sort != $column ) {
        $columnicon = "";
        $columndir  = "ASC";
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ( $column == "lastaccess" ) {
            $columnicon = $dir == "ASC" ? "up" : "down";
        } else {
            $columnicon = $dir == "ASC" ? "down" : "up";
        }
        $columnicon = " <img src=\"" . $OUTPUT->pix_url( 't/' . $columnicon ) . "\" alt=\"\" />";
    }
    $$column = "<a href=\"?sort=$column&amp;dir=$columndir\">" . $string[$column] . "</a>$columnicon";
}
if ( $sort == "name" ) {
    $sort = "firstname";
}
list( $extrasql, $params ) = $ufiltering->get_sql_filter();

// handle custom sql for moderngovernor
if (!empty($fieldnames)) {
    foreach ($fieldnames as $fn) {
        if ('confirmed' == $fn) { // confirmed is a special case ...
            $extrasql = str_replace($fn, "gu.{$fn}", $extrasql);
        } else {
            $extrasql = str_replace($fn, "u.{$fn}", $extrasql);            
        }
    }
}

$users           = get_deleted_users_listing( $sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasql, $params, $context );
$usercount       = get_deleted_users( false );
$usersearchcount = get_deleted_users( false, '', false, null, "", '', '', '', '', '*', $extrasql, $params );
if ( $extrasql !== '' ) {
    echo $OUTPUT->heading( "$usersearchcount / $usercount " . get_string( 'users' ) );
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading( "$usercount " . get_string( 'users' ) );
}
$baseurl = new moodle_url( 'settings.php', array(
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage
) );

if ( !$users ) {
    $match = array();
    echo $OUTPUT->heading( get_string( 'nousersfound' ) );
    $table = NULL;
} else {
    $override            = new stdClass();
    $override->firstname = 'firstname';
    $override->lastname  = 'lastname';
    $fullnamelanguage    = get_string( 'fullnamedisplay', '', $override );
    if ( ( $CFG->fullnamedisplay == 'firstname lastname' ) or ( $CFG->fullnamedisplay == 'firstname' ) or ( $CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' ) ) {
        $fullnamedisplay = "$firstname / $lastname";
    } else {
        $fullnamedisplay = "$lastname / $firstname";
    }
    $table          = new html_table();
    $table->head    = array();
    $table->align   = array();
    $table->head[]  = $fullnamedisplay;
    $table->align[] = 'left';
    $table->head[]  = $email;
    $table->align[] = 'left';
    $table->head[]  = 'time of removal';
    $table->align[] = 'left';
    $table->head[]  = 'username';
    $table->align[] = 'left';
    $table->head[]  = 'undelete';
    $table->align[] = 'center';

    $table->width   = "95%";
    foreach ( $users as $user ) {
        if ( isguestuser( $user ) ) {
            continue;
        }
        $adjustedemail = substr($user->email, 0, strrpos( $user->email, '.'));
        $deltime       = substr($user->email,  strrpos( $user->email, '.')+1);
        $fullname      = fullname( $user, true );
        $row           = array();
        $row[]         = $fullname;
        $row[]         = $adjustedemail;
        $row[]         = date("d-M-Y", (int)$deltime);
        $row[]         = $user->username;
        $row[]         = "<a href=\"?do_undelete=1&user_id=$user->id\">undelete</a>";
        $table->data[] = $row;
    }
}
$ufiltering->display_add();
$ufiltering->display_active();
if ( !empty( $table ) ) {
    echo html_writer::table( $table );
    echo $OUTPUT->paging_bar( $usercount, $page, $perpage, $baseurl );
}
if(isset($alert_text) ){
    echo "<script type=\"text/javascript\">alert('$alert_text');</script>";
}
echo $OUTPUT->footer();

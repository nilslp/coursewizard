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

/**
 * Implements a soft delete of users - partner of admin.user.js.php
 * - the delete controls on admin/user.php will redirect to this page
 *   where we can use our own delete functionality. 
 *
 * @package    local
 * @subpackage dlelegacytools
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
global $CFG,$OUTPUT;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/local/dlelegacytools/interface/lib.php');

// really we only care about $delete and $confirm but we can forward
// the other parameters on DPMH
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page

admin_externalpage_setup('editusers');

$sitecontext = get_context_instance(CONTEXT_SYSTEM);

if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}

if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$returnurl = new moodle_url('/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));
$thisurl = new moodle_url('/local/dlelegacytools/interface/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));

if ($delete and confirm_sesskey()) {              // we shouldn't really get to this page if this is not true
    require_capability('moodle/user:delete', $sitecontext);

    $user = $DB->get_record('user', array('id'=>$delete, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

    if (is_siteadmin($user->id)) {
        print_error('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), new moodle_url($thisurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted() and !$user->deleted) {
        // here we call our custom delete function DPMH
        if (dlt_delete_user($user)) {
            session_gc(); // remove stale sessions
            redirect($returnurl);
        } else {
            session_gc(); // remove stale sessions
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
}

// if the user somehow ended up here without meaning to, fire them back to the users page
redirect($returnurl);

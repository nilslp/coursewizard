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
 * This plugin can help upgrade site with a large number of question attempts
 * from Moodle 2.0 to 2.1.
 *
 * This screen is the main entry-point to the plugin, it gives the admin a list
 * of options available to them.
 *
 * @package    local
 * @subpackage lpscheduler
 * @copyright  2012 Learning Pool (Rachael Harkin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /** INCLUDES
  * **/
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot . '/local/learningpool/js/setup.php');
require_once($CFG->dirroot.'/local/dlelegacytools/lpscheduler/lib.php');


/** LOGON AND ROLE CAPABILITY **/
require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

/** Setting up page url for access to the plugin view**/
admin_externalpage_setup('lpscheduler');

global $USER, $PAGE, $_POST;

// rendering the page
$PAGE->requires->css('/local/dlelegacytools/style.css');
include_jquery();
$PAGE->requires->js('/local/dlelegacytools/javascript/lpscheduler_behaviours.js');
         
$lpscheduler_lib = new lpscheduler_lib(); 
$mform = new local_lpscheduler_index_form();

// get current settings for configs

$configs = get_config('local/dlelegacytools/lpscheduler/','lpscheduler');

echo $OUTPUT->header();

$fromform = $mform->get_data();

if ($fromform) {

    if(empty($fromform->submitbutton)) {
       echo $OUTPUT->notification(get_string('lpscheduler_settings_error','local_dlelegacytools'), 'notifyfailure');
    }

    if($lpscheduler_lib->lpscheduler_update_instance($fromform)) {
        echo $OUTPUT->notification(get_string('update_success','local_dlelegacytools'), 'notifysuccess');
    } 
    else {
       echo $OUTPUT->notification(get_string('update_fail','local_dlelegacytools'), 'notifyfailure');
    }
}

$description = get_string('lpscheduler_description','local_dlelegacytools');
echo html_writer::tag('div',$description,array('class'=>'description')); 

// display the form
$mform->display();

echo $OUTPUT->footer();



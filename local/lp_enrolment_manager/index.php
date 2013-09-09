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
 * This plugin allow bulk enrolment of users on any existing courses through the 
 * lp_hierarchy structure as well as in large custom selected groups. Admins can 
 * also customise the email that is sent to users when they are enrolled on a 
 * course.
 *
 * This screen is the main entry-point to the plugin, it gives the admin a list 
 * of options available to them.
 *
 * @package    local
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

$context = get_context_instance(CONTEXT_SYSTEM);

require_login();
require_capability('local/lp_enrolment_manager:enrolusers', $context);

$PAGE->set_url(new moodle_url('/local/lp_enrolment_manager/index.php'));
$PAGE->set_context($context);

admin_externalpage_setup('lp_enrolment_manager','',null,'',array('pagelayout'=>'standard'));

$renderer = $PAGE->get_renderer('local_lp_enrolment_manager');

$PAGE->requires->yui_module('moodle-local_lp_enrolment_manager-emaileditor', 'M.local_lp_enrolment_manager.init_emaileditor',array( 
    array (
        'target'    =>  'lpemaileditor',
        'container' =>  'lpemaildiv',
        'body'      =>  get_string('defaultemailbody','local_lp_enrolment_manager')
    )
));

$jsconfig = array(
	'name' => 'local_lp_enrolment_manager',
	'fullpath' => '/local/lp_enrolment_manager/lpenrolmentmanager.js',
	'requires' => array(
                        'node',
                        'base', 
                        'overlay',
                        'json',
                        'io',
                        'event',
                        'node-event-simulate',
                        'moodle-local_lp_enrolment_manager-emaileditor', 
                        'yui2-treeview',
                        'yui2-container',
                        'yui2-dragdrop' 
                    )
            );

$PAGE->requires->js_init_call('M.local_lp_enrolment_manager.init', null, false, $jsconfig);
#$PAGE->requires->css('/local/lp_enrolment_manager/styles.css');

echo $renderer->index_page();

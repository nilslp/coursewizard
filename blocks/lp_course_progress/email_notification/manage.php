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
 *
 * This screen is the main entry-point to the plugin, it gives the admin a list 
 * of options available to them.
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $SITE,$CFG,$OUTPUT,$PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');

$context = context_system::instance(); //get_context_instance(CONTEXT_SYSTEM);
$pagetitle = get_string('manageemailnotifications', 'block_lp_course_progress');

require_login();
require_capability('block/lp_course_progress:managenotifications', $context);

$PAGE->set_url(new moodle_url('/blocks/lp_course_progress/email_notification/manage.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($pagetitle);

$renderer = $PAGE->get_renderer('block_lp_course_progress');
        
echo $OUTPUT->header();

echo $renderer->manage_notifications();

// print table for notifications
$manager = new block_lp_course_progress_emailmanager();
$manager->print_notification_schedule();


echo $OUTPUT->footer();


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
 * Basic readme file in English.
 *
 * @package   block_calendar_month (replacement)
 * @copyright 2011-2012 Vencislav Dzhambazov, New Bulgarian University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = @$_GET["id"];

if ($id) {
  require_login();
  $context = context_course::instance($id, MUST_EXIST);// get_context_instance(CONTEXT_COURSE, $id, MUST_EXIST);
  $PAGE->set_context($context);
  $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
  $PAGE->set_course($course);
} else {
  $context = context_course::instance($SITE->id);// get_context_instance(CONTEXT_COURSE, SITEID);
  $PAGE->set_context($context);
  $PAGE->set_course($SITE);
}

require_once('../moodleblock.class.php');
require_once('block_calendar_month.php');

$var = optional_param('var', '', PARAM_ALPHA);
if ($var != '') {
    switch($var) {
        case 'showgroups':
            calendar_set_event_type_display(CALENDAR_EVENT_GROUP);
            break;
        case 'showcourses':
            calendar_set_event_type_display(CALENDAR_EVENT_COURSE);
            break;
        case 'showglobal':
            calendar_set_event_type_display(CALENDAR_EVENT_GLOBAL);
            break;
        case 'showuser':
            calendar_set_event_type_display(CALENDAR_EVENT_USER);
            break;
    }
}

$c= new block_calendar_month;
$c->page=$PAGE;
$cc=$c->get_content();
echo $cc->text;

$x= $PAGE->requires->get_end_code();
$x=explode('</script>',$x);
echo ''.$x[count($x)-2].'</script>';
?>

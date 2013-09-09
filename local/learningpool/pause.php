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

require_once("../../config.php");

$delay = optional_param('delay', 3, PARAM_INT);      // optionally set the delay time
$courseid = optional_param('courseid', 0, PARAM_INT);   // course id

$PAGE->set_url('/local/learningpool/pause.php');

// all this page does is create a pause before forwarding the user to the targeted course
redirect($CFG->wwwroot.'/course/view.php?id='.$courseid, get_string('pausemessage', 'local_learningpool'), $delay);
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
 * Handles any ajax requests - gets enrolled users, gets available users, etc
 *
 * @package    local
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_url(new moodle_url('/local/lp_enrolment_manager/ajax.php'));
$PAGE->set_context($context);

if (!isloggedin()){
    echo json_encode(array('success' => 'false', 'message' => get_string('sessionerroruser', 'error'), 'redirect' => $CFG->wwwroot.'/login/index.php'));
    exit;
}

require_login();
require_capability('local/lp_enrolment_manager:enrolusers', $context);

$result     = array('success' => 'false', 'message' => get_string('unrecognizedaction', 'local_lp_enrolment_manager'));
$action     = required_param('action', PARAM_ALPHANUMEXT);
$courseid   = optional_param('id', -1, PARAM_INT);
$manager    = new local_lp_enrolment_manager_usermanager($courseid);

if ($courseid === -1) {
    $result['message'] = get_string('pleaseselectcourse', 'local_lp_enrolment_manager');
} 
else if (method_exists($manager, $action)) {
    $result = $manager->$action();
    $result['action'] = $action;
}

echo json_encode( $result );
exit;
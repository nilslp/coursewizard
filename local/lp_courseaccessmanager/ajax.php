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
 * Handles any ajax requests 
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

ob_start();

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_url(new moodle_url('/local/lp_courseaccessmanager/ajax.php'));
$PAGE->set_context($context);

if (!isloggedin()){
    ob_clean();
    ob_end_clean();
    echo json_encode(array('success' => 'false', 'message' => get_string('sessionerroruser', 'error'), 'redirect' => $CFG->wwwroot.'/login/index.php'));
    exit;
}

require_login();
require_capability('local/lp_courseaccessmanager:manageaccess', $context);


$result         = array('success' => 'false', 'message' => get_string('unrecognizedaction', 'local_lp_courseaccessmanager'));
$action         = required_param('action', PARAM_ALPHANUMEXT);
$manager        = new local_lp_courseaccessmanager_manager();

if (method_exists($manager, $action)) {
    $result = $manager->$action();
    $result['action'] = $action;
}

ob_clean();
ob_end_clean();
echo json_encode( $result );
exit;
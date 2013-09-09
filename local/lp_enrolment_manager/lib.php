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
 * Lib functions (cron) to send emails remaining after large enrolments are
 * performed
 *
 * @package    local
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once (dirname(__FILE__) . '/locallib.php');

/**
 * Standard cron function
 */
function local_lp_enrolment_manager_cron() {
    local_lp_enrolment_manager_trace('local_lp_enrolment_manager_cron() started at '. date('H:i:s'));
    try {
        local_lp_enrolment_manager_process();
    } catch (Exception $e) {
        local_lp_enrolment_manager_trace('local_lp_enrolment_manager_cron() failed with an exception:');
        local_lp_enrolment_manager_trace($e->getMessage());
    }
    local_lp_enrolment_manager_trace('local_lp_enrolment_manager_cron() finished at ' . date('H:i:s'));
}

/**
 * This function does the cron process within the time range according to settings.
 */
function local_lp_enrolment_manager_process() {
    $msg = local_lp_enrolment_manager_sendqueuedemail(true);
    local_lp_enrolment_manager_trace($msg);
}

/**
 * helper function to print our messages consistently
 */
function local_lp_enrolment_manager_trace($msg) {
    mtrace('lp_enrolment_manager: ' . $msg);    
}

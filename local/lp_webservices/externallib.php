<?php

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
 * External Web Service For Learning Pool
 *
 * @package    local
 * @subpackage lp_webservices
 * @copyright  2013 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/completionlib.php");

class local_lp_webservices_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_completion_status_parameters() {
        return new external_function_parameters(
                array(
                    'courseid' => new external_value(PARAM_INT, 'The ID of the course to check', VALUE_REQUIRED),
                    'username' => new external_value(PARAM_RAW, 'The username of the user to check', VALUE_REQUIRED)
                    )
        );
    }

    /**
     * Gets the completion status on a given course for a given user
     * 
     * @global type $USER
     * @global type $DB
     * @param type $courseid
     * @param type $username
     * @return type
     * @throws Exception 
     */
    public static function get_completion_status($courseid, $username) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_completion_status_parameters(),
                array('courseid' => $courseid, 'username' => $username));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);
        
        if (!has_capability('local/lp_webservices:metacompliance', context_system::instance())) {
            throw new webservice_access_exception(get_string('accesstofunctionnotallowed', 'webservice', 'local_learningpool_get_completion_status'));
        }
        
        $completed = $DB->get_field_sql("SELECT timecompleted FROM {course_completions} cc JOIN {user} u ON u.id=cc.userid WHERE u.username = ? AND cc.course = ?", array($username, $courseid));
        
        // cast as int/bool
        $completed = empty($completed) ? 0 : 1;
        
        return $completed;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_completion_status_returns() {
        return new external_value(PARAM_INT, 'Indicates whether the course is complete');
    }
    
}

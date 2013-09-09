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
 * Premium enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol
 * @subpackage premium
 * @copyright  2013 Brian Quinn / Kevin Corry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
/**
 * Premium enrolment plugin implementation.
 * @author  Eugene Venter - based on code by Martin Dougiamas and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_premium_plugin extends enrol_plugin {

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        return array(new pix_icon('icon', get_string('pluginname', 'enrol_premium'), 'enrol_premium'));
    }

    public function roles_protected() {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users manually - requires enrol/premium:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status - requires enrol/premium:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'premium') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);
        if (has_capability('enrol/premium:config', $context)) {
            $managelink = new moodle_url('/enrol/premium/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'premium') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);

        $icons = array();

        if (has_capability('enrol/premium:config', $context)) {
            $editlink = new moodle_url("/enrol/premium/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = get_context_instance(CONTEXT_COURSE, $courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/premium:config', $context)) {
            return NULL;
        }

        // multiple instances supported - different cost for different roles
        return new moodle_url('/enrol/premium/edit.php', array('courseid'=>$courseid));
    }

     /**
     * Send welcome email to specified user
     *
     * @param object $instance
     * @param object $user user record
     * @return void
     */
    public function email_welcome_message($instance, $user) {
        global $CFG, $DB;

        $course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname);
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";

        if (trim($instance->customtext1) !== '') {
            // Message is HTML
            $message = $instance->customtext1;
            $message = str_replace('{$a->coursename}', $a->coursename, $message);
            $message = str_replace('{$a->profileurl}', $a->profileurl, $message);
        } else {
            $message = get_string('welcometocoursetext', 'enrol_premium', $a);
        }

        $subject = get_string('welcometocourse', 'enrol_premium', format_string($course->fullname));

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $rusers = array();
        if (!empty($CFG->coursecontact)) {
            $croles = explode(',', $CFG->coursecontact);
            $rusers = get_role_users($croles, $context, true, '', 'r.sortorder ASC, u.lastname ASC');
        }
        if ($rusers) {
            $contact = reset($rusers);
        } else {
            $contact = generate_email_supportuser();
        }

        //directly emailing welcome message rather than using messaging
        if (trim($instance->customtext1) !== '') {
            // A HTML email template has been saved
            email_to_user($user, $contact, $subject, strip_tags($message), $message);
        }
        else {
            email_to_user($user, $contact, $subject, $message);
        }
    }
    
    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    function enrol_page_hook(stdClass $instance) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id'=>$instance->courseid));
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ( (float) $instance->cost <= 0 ) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_premium').'</p>';
        } else {

            $userisinternal = false;
            if (file_exists($CFG->dirroot . '/blocks/internal_hierarchy/lib.php')) {
                require_once $CFG->dirroot . '/blocks/internal_hierarchy/lib.php';
                // Check if this is an internal or external user
                $userisinternal = is_internal_user();
            }
            
            // Check if the user has access to this course via a license
            $license = get_user_course_license($course->id);
            if( $license && get_config('enrol_premium','autoenrol')==0) {
                // Present users with an 'Enrol me' button
                require_once("$CFG->dirroot/enrol/premium/locallib.php");
                $form = new premium_enrol_manual_form(NULL, $instance);
                if ($data = $form->get_data()) {
                    // Calculate the end date of the enrolment (if it has not been defined)
                    $update = false;
                    if (!isset($license->enddate)) {
                        $license->startdate = time();
                        $license->enddate = $license->startdate + $license->enrolperiod;
                        $update = true;
                    }
                    enrol_premium_user($course, $USER->id, $license->startdate, $license->enddate);
                    
                    // Update our license
                    if($update) {
                        $DB->update_record('shopping_basket_license', $license);
                    }
                }
                
                ob_start();
                echo $form->display();
                
            } else if($userisinternal && get_config('enrol_premium','freeinternal')==1) {
                // Present internal users with an 'Enrol me' button if necessary
                require_once("$CFG->dirroot/enrol/premium/locallib.php");
                $form = new premium_enrol_internal_form(NULL, $instance);
        
                if ($data = $form->get_data()) {
                    $timestart = time();
                    // This user can enjoy the course forever!
                    $timeend = 0;
                    enrol_premium_user($course, $USER->id, $timestart, $timeend);
                }
                
                ob_start();
                echo $form->display();
                
            } else {
            
                if (isguestuser()) { // force login only for guest user, not real users with guest role
                    if (empty($CFG->loginhttps)) {
                        $wwwroot = $CFG->wwwroot;
                    } else {
                        // This actually is not so secure ;-), 'cause we're
                        // in unencrypted connection...
                        $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
                    }

                    echo '<div id="enrol_area" align="center">';
                    echo get_enrolment_html($instance->courseid);
                    echo '</div>';
                } else {
                    //Sanitise some fields before building the PayPal form
                    $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                    $courseshortname = $shortname;
                    $userfullname    = fullname($USER);
                    $userfirstname   = $USER->firstname;
                    $userlastname    = $USER->lastname;
                    $useraddress     = $USER->address;
                    $usercity        = $USER->city;
                    $instancename    = $this->get_instance_name($instance);

                    include($CFG->dirroot.'/enrol/premium/enrol.html');
                }
            }
        }

        return $OUTPUT->box(ob_get_clean());
    }

}

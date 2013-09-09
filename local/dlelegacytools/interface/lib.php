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
 * Legacy scorm functionality managed here
 *
 * @package    local
 * @subpackage dlelegacytools
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

define('DLT_ALTERNATE_FORGOTTEN_PASSWORD_URL', $CFG->wwwroot . '/local/dlelegacytools/interface/login/forgot_password.php');

/**
 * Scorm settings form class
 */
class local_dlt_lafsettingsform extends moodleform {

    function definition() {
        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('lafsettings', 'local_dlelegacytools'));

        $mform->addElement(
                'select', 'questcoursebtn', get_string('questcoursebtn', 'local_dlelegacytools'), array(
            'hide' => get_string('hide', 'local_dlelegacytools'),
            'show' => get_string('show', 'local_dlelegacytools')
        ));

        $showbtn = get_config('local/dlelegacytools', 'questcoursebtn');
        if ($showbtn) {
            $mform->setDefault('questcoursebtn', $showbtn);
        }

        $mform->addElement('checkbox', 'confirmdelete', get_string('confirmdelete', 'local_dlelegacytools'));

        $confirmdelete = get_config('local/dlelegacytools', 'confirmdelete');

        if ($confirmdelete) {
            $mform->setDefault('confirmdelete', $confirmdelete);
        }

        $mform->addElement(
                'select', 'customforgottenurl',
                get_string('customforgottenurl', 'local_dlelegacytools'), 
                array(
                    'off' => get_string('off', 'local_dlelegacytools'),
                    'on' => get_string('on', 'local_dlelegacytools')        
                    )
                );

        // this setting is really just an alias for a standard moodle config - this setting
        // just makes it clearer for someone used to the DLE Configuration tool. DPMH
        $customforgottenurl = get_config('moodle','forgottenpasswordurl');

        if (!empty($customforgottenurl)) {
            $mform->setDefault('customforgottenurl', 'on');
        } else {
            $mform->setDefault('customforgottenurl', 'off');
        }

        // hidden profile fields
        $mform->addElement(
            'select', 'hiddenprofilefields',
            get_string('hiddenprofilefields', 'local_dlelegacytools'),
            array(
                'off' => get_string('off', 'local_dlelegacytools'),
                'on' => get_string('on', 'local_dlelegacytools')        
                )
            );
        
        $hiddenprofilefields = get_config('local/dlelegacytools','hiddenprofilefields');

        if (!empty($hiddenprofilefields)) {
            $mform->setDefault('hiddenprofilefields', $hiddenprofilefields);
        }
		
		//added by rwm 24/04/2012 replace $DLE[96]
		//start:
		$mform->addElement(
			'select','loginredirect', get_string('loginredirect','local_dlelegacytools'),
			array(
				'off' => get_string('off', 'local_dlelegacytools'),
                'on' => get_string('on', 'local_dlelegacytools') ,
                'force' => get_string('force', 'local_dlelegacytools') 
			)
		);
		
		$loginredirect = get_config('local/dlelegacytools','loginredirect');
		if(!empty($loginredirect)){
			$mform->setDefault('loginredirect', $loginredirect);
		}else{
			$mform->setDefault('loginredirect', 'off');
		}
		
		$mform->addElement('text', 'loginredirect_target', get_string('loginredirect_target', 'local_dlelegacytools'));
                $mform->setType('loginredirect_target', PARAM_RAW);
		
		$loginredirect_trg = get_config('local/dlelegacytools','loginredirect_target');
		if(!empty($loginredirect_trg)){
			$mform->setDefault('loginredirect_target', $loginredirect_trg);
		}		

		$mform->disabledIf('loginredirect_target','loginredirect','eq','off');		//end:
        
        // use this block in place of default
        $mform->addElement(
            'select', 'customlogin',
            get_string('usethisblock', 'local_dlelegacytools'),
            array(
                0 => get_string('off','local_dlelegacytools'),
                1 => get_string('on','local_dlelegacytools')
                )
            );
        
        $customlogin = get_config('local/dlelegacytools','customlogin');

        if (!empty($customlogin)) {
            $mform->setDefault('customlogin', $customlogin);
        }
        
        $mform->addElement('checkbox', 'logincheckextrafields', get_string('logincheckextrafields', 'local_dlelegacytools'));

        $logincheckextrafields= get_config('local/dlelegacytools', 'logincheckextrafields');

        if ($logincheckextrafields) {
            $mform->setDefault('logincheckextrafields', $logincheckextrafields);
        }
                
        $mform->addElement('checkbox', 'redirectbuttononconfirm', get_string('redirectbuttononconfirm', 'local_dlelegacytools'));

        $redirectbuttononconfirm = get_config('local/dlelegacytools', 'redirectbuttononconfirm');

        if ($redirectbuttononconfirm) {
            $mform->setDefault('redirectbuttononconfirm', $redirectbuttononconfirm);
        }

		$mform->addElement('checkbox', 'wipepolicy', get_string('wipe_policy_desc', 'local_dlelegacytools'));
		$wipe_policy = get_config('local/dlelegacytools','wipe_policy');
		if($wipe_policy){
			$mform->setDefault('wipepolicy', $wipe_policy);
		}
        
        $this->add_action_buttons();
    }

    function process() {
        global $OUTPUT,$DB;
        $formdata = $this->get_data();

        if (!$formdata) {
            return;
        }

        if (empty($formdata->submitbutton)) {
            echo $OUTPUT->notification(get_string('update_fail', 'local_dlelegacytools'), 'notifyfailure');
            return;
        }

        $success = false;
        if (isset($formdata->questcoursebtn)) {
            $success = set_config('questcoursebtn', $formdata->questcoursebtn, 'local/dlelegacytools');
        }

        if ($success && isset($formdata->confirmdelete)) {
            $success = set_config('confirmdelete', $formdata->confirmdelete, 'local/dlelegacytools');
        }

        // this setting is a proxy for the standard moodle config forgottenpasswordurl, but locks it down
        // to a form defined by this plugin. DPMH
        if ($success && isset($formdata->customforgottenurl)) {
            if ('on' == $formdata->customforgottenurl) {
                $success = set_config('forgottenpasswordurl', DLT_ALTERNATE_FORGOTTEN_PASSWORD_URL);
            } else {
                $success = set_config('forgottenpasswordurl', '');
            }
        }
         
        if ($success && isset($formdata->hiddenprofilefields)) {
            $success = set_config('hiddenprofilefields', $formdata->hiddenprofilefields, 'local/dlelegacytools');
        }
		
		if ($success && isset($formdata->loginredirect)) {
            $success = set_config('loginredirect', $formdata->loginredirect, 'local/dlelegacytools');
        }
		
		if ($success && isset($formdata->loginredirect_target)) {
            $success = set_config('loginredirect_target', $formdata->loginredirect_target, 'local/dlelegacytools');
        }        
        
        if ($success && isset($formdata->customlogin)) {
            $success = set_config('customlogin', $formdata->customlogin, 'local/dlelegacytools');
            $overridelogin = get_config('local/dlelegacytools','customlogin');
            if ($overridelogin) {
                $DB->execute("UPDATE {block_instances} SET blockname = 'lp_login' WHERE blockname = 'login'");
            } else {
                $DB->execute("UPDATE {block_instances} SET blockname = 'login' WHERE blockname = 'lp_login'");       
            }
        }

		if ($success && isset($formdata->logincheckextrafields)) {
            $success = set_config('logincheckextrafields', $formdata->logincheckextrafields, 'local/dlelegacytools');
        }
        
		if ($success && isset($formdata->redirectbuttononconfirm)) {
            $success = set_config('redirectbuttononconfirm', $formdata->redirectbuttononconfirm, 'local/dlelegacytools');
        }

		if ($success && isset($formdata->wipepolicy)) {
            $success = set_config('wipe_policy', $formdata->wipepolicy, 'local/dlelegacytools');
        }
        
        if ($success) {
            echo $OUTPUT->notification(get_string('update_success', 'local_dlelegacytools'), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('update_fail', 'local_dlelegacytools'), 'notifysuccess');
        }
    }

}

function configure_interface_behavior() {
    global $PAGE, $DB, $OUTPUT;

    if (!isset($PAGE) || !$PAGE->has_set_url()) {
        // some core moodle stuff doesn't follow the rules :-O
        return;
    }

    if (false !== strpos($PAGE->url, '/mod/questionnaire/myreport.php')) {
        $params = array();

        // we want to add the return to course button here!
        if ('show' == get_config('local/dlelegacytools', 'questcoursebtn')) {
            $instance = optional_param('instance', 0, PARAM_INT);
            $questionnaire = $DB->get_record("questionnaire", array('id' => $instance), 'id,course');
            if ($questionnaire) {
               $params['courselink'] = '<form method="get" action="/course/view.php"><div><input type="submit" value="'.get_string('continue').'"><input type="hidden" name="id" value="'.$questionnaire->course.'"></div></form>';
            }
        }

        $PAGE->requires->css('/local/dlelegacytools/interface/laf.style.css.php' . (empty($params) ? '' : '?' . implode('&', $params)));
        
        $jsconfig = array(
            'name' => 'local_lp_dlelegacytools',
            'fullpath' => '/local/dlelegacytools/interface/laf.behaviors.js',
            'requires' => array(
                        'node',
                        'event'
                    )
            );
        
        $PAGE->requires->js_init_call('M.local_dlelegacytools_interface.init', array($params), false, $jsconfig);
        return;
    }
    
    if (false !== strpos($PAGE->url, '/login/index.php')) {
        $PAGE->requires->css('/local/dlelegacytools/interface/laf.style.css.php');
        return;
    }

    if (false !== strpos($PAGE->url, '/course/view.php')) {
        $confirmdelete = get_config('local/dlelegacytools', 'confirmdelete');

        if ($confirmdelete) {
            $sessionkey = sesskey();
            
            $jsconfig = array(
            'name' => 'local_dlelegacytools',
            'fullpath' => '/local/dlelegacytools/interface/laf.confirmmoddelete.js',
            'requires' => array(
                            'node',
                            'event'
                        )
                );

            $PAGE->requires->js_init_call('M.local_dlelegacytools_interface.init', array($sessionkey), false, $jsconfig);
        }
        return;
    }
    
    if (false !== strpos($PAGE->url, '/login/signup.php')) {
        $PAGE->requires->js('/local/dlelegacytools/interface/login.signup.js.php');
        return;
    }
    
    if (false !== strpos($PAGE->url, '/user/editadvanced.php')) {
        if ('on' == get_config('local/dlelegacytools','hiddenprofilefields')){
            $PAGE->requires->js('/local/dlelegacytools/interface/user.profile.js.php');
        }
        return;
    }
    
    if (false !== strpos($PAGE->url, '/login/confirm.php')) {
        if (get_config('local/dlelegacytools','redirectbuttononconfirm')){
            $PAGE->requires->js('/local/dlelegacytools/interface/login.confirm.js.php');
        }
        return;
    }
}

/**
 * Custom user delete function. Performs a soft delete of the user.
 * 
 * @global type $CFG
 * @global type $DB
 * @param type $user
 * @return boolean 
 */
function dlt_delete_user($user) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/grouplib.php');
    require_once($CFG->libdir . '/gradelib.php');

    $transaction = $DB->start_delegated_transaction();
    $synchierarchy = (int)get_config('local_moderngovernor', 'synchierarchy');
    
    /**
     * Soft delete 11/02/11 - original by rwm
     */
    //get a safe delete username
    $deluname = addslashes("$user->username.DELETED");

    //get a safe delete email
    $delemail = addslashes("$user->email." . time());
    while ($DB->record_exists('user', array('email' => $delemail))) { // no need to use mnethostid here
        $delemail++;
    }

    // mark internal user record as "deleted" with new email and username
    $updateuser = new stdClass();
    $updateuser->id = $user->id;
    $updateuser->deleted = 1;
    $updateuser->idnumber = $user->idnumber;
    $updateuser->timemodified = time();
    if (!$synchierarchy) { // only modify username and email where user is not synched upstream, otherwise, we break the join on the global table
        $updateuser->username = $deluname;
        $updateuser->email = $delemail;
    }
    
    try {
        if ($DB->update_record('user', $updateuser)) {  
            // if moderngovenor synch user is switched on, just delete user in global table.
            if ($synchierarchy) {
                $mgpath = $CFG->dirroot.'/local/moderngovernor/lib.php';
                if (file_exists($mgpath)) {
                    require_once($mgpath); 
                    if (!moderngovernor_delete_user($user->id)){
                        throw new Exception("Failed to update global table!");
                    }
                }        
            }
            $transaction->allow_commit();
            return true;
        }
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
    return false;
}

/**
 * dlt_send_password_change_confirmation_email.
 *
 * @global object
 * @param user $user A {@link $USER} object
 * @return bool Returns true if mail was sent OK and false if there was an error.
 */
function dlt_send_password_change_confirmation_email($user) {
    global $CFG;
    
    if (!isset($CFG->forgottenpasswordurl) || empty($CFG->forgottenpasswordurl)){
        // should never get here cause this function should only get called when
        // the above var is set, but .... DPMH
        return;
    }

    $site = get_site();
    $supportuser = generate_email_supportuser();

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname  = $user->lastname;
    $data->sitename  = format_string($site->fullname);
    // instead of sending password confirmation to the standard login page, we need to send it to
    // our custom forgotten password url! DPMH
    $data->link      = $CFG->forgottenpasswordurl.'?p='. $user->secret .'&s='. urlencode($user->username);
    $data->admin     = generate_email_signoff();

    $message = get_string('emailpasswordconfirmation', '', $data);
    $subject = get_string('emailpasswordconfirmationsubject', '', format_string($site->fullname));
    
    //directly email rather than using the messaging system to ensure its not routed to a popup or jabber
    return email_to_user($user, $supportuser, $subject, $message);

}

/**
 * Checks if there are additional profile fields that have yet to be filled in by the user
 *  - called on user login to redirect users complete their profiles
 * 
 * @global moodle_database $DB
 * @return boolean 
 */
function lp_additional_profile_fields_not_setup($user) {
    global $DB;
    
    // check we're not using log-in-as and check if there are unfilled profile fields
    $hasadmin = has_capability('moodle/user:update', context_system::instance());
    $checkextrafields = get_config('local/dlelegacytools','logincheckextrafields');
    if ($checkextrafields && !$hasadmin) {
        $sql = "SELECT COUNT(*) FROM {user_info_field} f 
                WHERE required = ? 
                    AND visible <> ? 
                    AND (
                        SELECT userid FROM {user_info_data} 
                        WHERE userid= ?  
                            AND fieldid=f.id 
                            AND data IS NOT NULL
                            AND data != ''
                    ) IS NULL";
        
        // if there are no entries in user data for required fields for this user, this will return true
        return $DB->get_field_sql($sql, array(1,0,$user->id));
    }
    
    return false;
}
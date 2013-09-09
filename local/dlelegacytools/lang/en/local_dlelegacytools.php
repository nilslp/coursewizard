<?php
/*
 * // This file is part of Moodle - http://moodle.org/
 */
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
 * English strings for lpscheduler
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   plugin_dlelegacytools
 * @copyright 2011 Learning Pool  Brian Quinn, Dennis Heaney,Rachael Harkin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']       = 'DLE Legacy Tools';
$string['pluginnameplural'] = 'DLE Legacy Tools\'';
$string['menuitem']         = 'DLE Legacy Tools';

/* SCHEDULER LANGUAGE SETTINGS**/
$string['lpscheduler_menuitem'] = 'DLE Scheduler';
$string['lpscheduler_submenuitem'] = 'Scheduler Settings';

// capabilities strings
$string['lpscheduler:view']     = $string['pluginname'].' View';
$string['lpscheduler:submit']   = 'Submission on '.$string['pluginname'];
// end capabilities strings

$string['lpscheduler_administration']   = 'DLE Leagcy Tools: Scheduler Administration';
$string['lpscheduler_backtoindex']      = 'Back to the '.$string['pluginname'];
$string['lpschedulername']              = 'Name';
$string['lpscheduler_description']      = 'The DLE Scheduler allows site admins to control what cron job is enabled for the DLE. Some of these settings are standard moodle cron jobs
                                            which get overridden by the settings that are applied below. Others are custom cron jobs specifically for the Learning Pool DLE,which are indicated below.';
$string['lpscheduler']                  = 'DLE Scheduler';

$string['lpscheduler_changes_saved']    = 'Changes Saved.';
$string['lpscheduler_nodata_error']     = 'No data to process.';
$string['lpscheduler_settings_saved']   = 'The new settings have been applied.';
$string['lpscheduler_settings_error']   = 'Settings have not been applied.';

$string['lpscheduler_update_button']    = 'Update';
$string['update_success']               = 'Update Sucessful';
$string['update_fail']                  = 'The update was unsuccessful. To check what happened try turning debugging mode on under site administration > development > debugging.';
$string['useroutput_index']             = 'You can choose which schedule tasks you want to enable for the DLE here.';

// SCHEDULER cron description strings

$string['runExtras']                = 'Run Extras <br> <span style="color:#aaa;">$CFG->runextras</span>';
$string['disablescheduledbackups']  = 'Disable scheduled backups <br> <span style="color:#aaa;">$CFG->disablescheduledbackups</span>';
$string['removeExpiredEnrolments']  = 'Remove expired enrolments <br> <span style="color:#aaa;">$CFG->removeExpiredEnrolments</span>';
$string['longtimenosee']            = 'Remove inactive accounts <br> <span style="color:#aaa;">$CFG->longtimenosee</span>';
$string['deleteincompleteusers']    = 'Delete incomplete users <br> <span style="color:#aaa;">$CFG->deleteincompleteusers</span>';
$string['deleteunconfirmed']        = 'Delete unconfirmed accounts <br> <span style="color:#aaa;">$CFG->deleteunconfirmed</span>';
$string['loglifetime']              = 'Log Lifetime <br> <span style="color:#aaa;">$CFG->loglifetime</span>';
$string['notifyloginfailures']      = 'Notify login failures <br> <span style="color:#aaa;">$CFG->notifyloginfailures</span>';
$string['sendNewPasswords']         = 'Send new passwords <br> <span style="color:#aaa;">$CFG->sendNewPasswords</span>';
$string['forceContext']             = 'Force context, rebuilds the contexts for moodle <br> <span style="color:#aaa;">$CFG->forceContext</span>';
$string['runEnrolment']             = 'Run enrolment <br> <span style="color:#aaa;">$CFG->runEnrolment</span>';
$string['runAuth']                  = 'Run Auth, relates to the authentication <br> <span style="color:#aaa;">$CFG->runAuth</span>';
$string['disablestatsprocessing']   = 'Disable stats processing <br> <span style="color:#aaa;">$CFG->disablestatsprocessing</span>';
$string['sendForumEmails']          = 'Send Forum Emails <br> <span style="color:#aaa;">$CFG->sendForumEmails</span>';
$string['forumEmailFrom']           = 'Forum Email From <br> <span style="color:#aaa;">$CFG->forumEmailFrom</span>';
$string['blockwelcomeemail']        = 'Enable welcome emails <br> <span style="color:#aaa;">If enabled, welcome emails will be sent using schedule defined in welcome email settings</span>';
$string['check_excel_user_import']  = 'Enable auto user upload from Excel<br> <span style="color:#aaa;">Norfolk cron job</span>';



/** END SCHEDULER LANGUAGE SETTINGS**/

// SCORM Language strings
$string['scormsettings']            = 'SCORM settings';
$string['scormpopup']               = 'Show popup blocker message';
$string['scormscoremsg']            = 'Score message displayed';
$string['scormredirect']            = 'Redirect on SCORM';
$string['scormhideenterbtn']        = 'Hide enter button on MultiSCO';
$string['show']                     = 'Show';
$string['hide']                     = 'Hide';
$string['on']                       = 'On';
$string['off']                      = 'Off';
$string['scormpopupmsg']            = 'Please disable your popup blocker, or add <strong>www.learningpool.com</strong> to the exceptions list.<br><u>Contact your IT support team for further details or assistance with accomplishing this.</u>';
$string['noscore']                  = 'No score information displayed';
$string['scoreonly']                = 'Only display the score attained';
$string['scoreandmax']              = 'Show the score compared with the maximum score';
$string['homepage']                 = 'Homepage';
$string['coursetopic']              = 'Course topic';
$string['coursecatlist']            = 'Course category list';
$string['coursecatpage']            = 'Category page for course';
$string['spnoscore']                = '{$a->title}';
$string['spscoreonly']              = '{$a->title}&nbsp;(Score: {$a->score})';
$string['spscoreandmax']            = '{$a->title}&nbsp;(Score: {$a->score} out of {$a->scoremax})';

//undelete users strings
$string['undeleteusers']            = 'Undelete Users';

// Look and feel strings
$string['lafsettings']              = 'Interface Settings';
$string['questcoursebtn']           = 'Course button on questionnaire page';
$string['confirmdelete']            = 'Confirm deletion of courses and modules';
$string['hiddenprofilefields']      = 'On - hidden fields will be invisible even to admin users, Off - default behaviour for hidden user fields';
$string['customforgottenurl']       = 'Password Reset Username Hide (Alternate forotten password url)';
$string['passwordforgotteninstructions'] = 'To reset your password, submit your email address below. If we can find you in the database, an email will be sent to that address with instructions on how to get access again.';

$string['status']                   = 'Status';
$string['date']                     = 'Date';

// welcome email strings
$string['welcomeemail_menu_name']   = 'Welcome Email Settings';
$string['schedulewelcomeemail']     = 'Schedule welcome email <br> <span style="color:#64696B;">Set the time of when the welcome notification is emailed to new users.Use pure 24hour number format, with no extra characters. <br>
                                       12:00pm = 1200. </span>';
$string['welcome_index_desc']       = 'In this section you can edit the automatic emails sent to users.';
$string['email_editor']             = 'Email Editor';

$string['new_user_email']           = 'New user email';
$string['new_user_cc']              = 'New User CC <br> <span style="color:#64696B;">The email address that will be alerted of the new account.</span>';
$string['new_user_subject']         = 'New User Email Subject <br> <span style="color:#64696B;">Email Subject</span>';
$string['new_user_email_body']      = 'New User Email Body <br> <span style="color:#64696B;">This is the email that will be sent to new users who are entered onto the system by administrators or bulk uploads</span>';
$string['enable_email_template']    = 'Enable Email template <br> <span style="color:#64696B;"> Turn this off if you want the default email from the system to be used.</span>';
$string['new_user_defined_fields']  = 'User-Defined Fields';
$string['new_user_defined_fields_descr'] = 'You can Insert the following user-defined fields that populate from the system <br> <username> <br>
                                            You can place this field anywhere in the body of the email and it will display the username of the person being sent the email.
                                            E.g. Your new username is: <username>.';
$string['new_user_email_from']     =  'Please enter the email address of the DLE administrator (Send email From this address)';

$string['self_reg_email']          = 'Self Registration Email';
$string['self_reg_email_cc']       = 'Self Registration email cc <br> <span style="color:#64696B;">This is the email that will be sent to users who use the self subscription functionality.</span>';
$string['self_reg_email_body']     = 'Self Registration email text <br> <span style="color:#64696B;">Hi < username ></span>';
$string['self_reg_welcome_link']   = 'Include "confirm account" link <br> <span style="color:#64696B;">Self Registration confirm account link. </span>';
$string['self_reg_confirmation_text'] = 'Self Registration confirmation text <br> <span style="color:#64696B;">If enabled, this text and the confirmation link will appear after "Hi < username >" and before the "Self Registration email text".</span>';

/** Login Redirect **/
$string['loginredirect']			= 'Redirect User on Login/Password change';
$string['force']					= 'Force';
$string['loginredirect_target']		= 'Redirect User to';
$string['loginredirect_target_req']	= 'Please specify a page to redirect the user to';
/** End Login Redirect **/

$string['lploginblocksettings'] = 'LP Login Block';
$string['usethisblock'] = 'Use the lp_login block as the default login block';
$string['logincheckextrafields'] = 'Check for blank extra profile fields on login?';
$string['backtowordpress'] = 'My Community Hub';
$string['redirectbuttononconfirm'] = 'Redirect to wordpress.';

$string['wipe_policy_desc'] = "Require user to agree to site policy on each login.";

/** Header Text **/
$string['head_of_page_signup'] = ''; 
/** End Header Text **/

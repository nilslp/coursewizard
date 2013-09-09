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
 * Strings for component 'enrol_premium', language 'en'
 *
 * @package    enrol
 * @subpackage premium
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignrole'] = 'Assign role';
$string['cost'] = 'Enrol cost';
$string['costerror'] = 'The enrolment cost is not numeric';
$string['costorkey'] = 'Please choose one of the following methods of enrolment.';
$string['currency'] = 'Currency';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during Premium enrolments';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid (in seconds). If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can be enrolled from this date onward only.';
$string['mailadmins'] = 'Notify admin';
$string['mailstudents'] = 'Notify students';
$string['mailteachers'] = 'Notify teachers';
$string['messageprovider:premium_enrolment'] = 'Premium enrolment messages';
$string['nocost'] = 'There is no cost associated with enrolling in this course!';
$string['premium:config'] = 'Configure Premium enrol instances';
$string['premium:manage'] = 'Manage enrolled users';
$string['premium:unenrol'] = 'Unenrol users from course';
$string['premium:unenrolself'] = 'Unenrol self from the course';
$string['paypalaccepted'] = 'PayPal payments accepted';
$string['pluginname'] = 'Premium';
$string['pluginname_desc'] = 'The Premium module allows you to set up paid courses.  It works in conjunction with the Shopping Basket block plugin.  The costs involved for accessing specific courses are defined by the products associated with the Shopping Basket plugin.';
$string['sendpaymentbutton'] = 'Send payment via PayPal';
$string['status'] = 'Allow Premium enrolments';
$string['status_desc'] = 'Allow users to enrol via purchasing a Premium course into a course by default.';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['alreadyinbasket'] = 'Your enrolment has already been added to your {$a}.';
$string['confirmenrolment'] = 'Please click on {$a} to pay for and complete your enrolment.';
$string['freeinternal'] = 'Free internal user enrolment';
$string['freeinternal_def'] = 'This option allows internal users to enrol on paid courses for free';
$string['autoenrol'] = 'Automatically enrol users after purchase';
$string['autoenrol_def'] = 'If you select this option, users will be automatically enrolled on purchased courses. Otherwise, they must manually click "Enrol Me" to enrol.';
$string['enrolme'] = 'Enrol me';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page so that we can learn more about you:

  {$a->profileurl}';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'If enabled, users receive a welcome message via email when they enrol in a course.';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'Enter the message which you would like to be emailed to a user when they enrol on the course.
    
Note that you can use the following placeholders:
{$a->coursename} - for the name of the course
{$a->profileurl} - for a link to the user\'s course profile';
$string['premiummanualenrol'] = 'You have access to enrol on this course, please click Enrol Me';
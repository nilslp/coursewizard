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
 * Strings for component 'block_course_progress', language 'en', branch 'MOODLE_2.2.1_STABLE'
 *
 * @package   block_course_progress
 * @copyright 2012 onwards Rachael@LearningPool (http://www.learningpool.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Learning Pool - Course Progress';
$string['blocktitle'] = 'My Course Progress';

// tabs on full progress page
$string['incomplete_title'] = 'Incomplete';
$string['complete_title'] = 'Complete';
$string['nonattempted_title'] = 'Not Attempted';
$string['by_category']= 'By Category';
$string['show_all'] = 'All';

$string['settingscoreoptionnone'] = 'No score information';
$string['settingscoreoptionscoreonly'] = 'Only display the score achieved';
$string['settingscoreoptionall'] = 'Show the score and the maximum possible score';

$string['showallcourses'] = 'Display all courses';
$string['showallcourses_def'] = 'When enabled all courses will be visibile, even ones which the user has not enrolled on';
$string['persistordering'] = 'Persist ordering';
$string['persistordering_def'] = 'When enabled the sort ordering of modules for display will be the same as used on the course summary page';
$string['scoremessagedisplayoption'] = 'Quiz score display format';
$string['scoremessagedisplayoption_def'] = 'This controls the format of the quiz scoring';
$string['filternocourseid'] = 'Filter courses with no Course ID';
$string['filternocourseid_def'] = 'If checked, courses that have no Course ID set will not be shown in the student\'s progress';
$string['overrideselfcomplete'] = 'Override activities where manual completion is enabled.';
$string['overrideselfcomplete_def'] = 'If enabled, scorm/quiz activities that the user can mark as complete will be ignored in and will use score or scorm progress instead.';

$string['quizzes'] = 'Quizzes';
$string['elearning'] = 'E-learning resources';
$string['scoreseparator'] = '/';

$string['status_incomplete'] = 'Incomplete';
$string['status_not_attempted'] = 'Not attempted';
$string['status_complete'] = 'Complete';
$string['status_by_category'] = 'By category';
$string['status_all'] = 'All';
$string['status_enrolled'] = 'Enrolled';
$string['status_notenrolled'] = 'Not Enrolled';

$string['manageemailnotifications']     = 'Incomplete Course Notifications';
$string['editemailnotifications']       = 'Edit Scheduled Email';
$string['notifcationscheduleheading']   = 'Scheduled emails';
$string['manageemailheading']           = 'Incomplete Course Email Notification';
$string['noscheduledemails']            = 'You have no notifications scheduled.';
$string['manageemailinstruction']       = '<p>With this option you can set up a weekly or monthly email to alert users of their incomplete courses. You can create multiple different emails. Emails can be set to run for all courses or only particular courses and to run permanently or only for a defined time period.</p>
<p>Dates are displayed in <strong>d/m/y</strong> format.</p>
<p>The email will detail the course name, the number of sections, date started, and date last accessed.</p>';
$string['setupnewnotification']         = 'Setup new email notification';
$string['notificationid']               = 'ID';
$string['notificationdesc']             = 'Description';
$string['notificationmsg']              = 'Email Body';
$string['notificationstart']            = 'Start Date';
$string['notificationend']              = 'End Date';
$string['notificationlimitcourses']     = 'Limit by courses?';
$string['notificationcoursesall']       = 'All courses';
$string['notificationcourseslist']      = 'Only selected courses';
$string['notificationfreq']             = 'Frequency';
$string['notificationnext']             = 'Next';
$string['notificationstatus']           = 'Status';
$string['notificationaction']           = 'Action';
$string['notificationnoend']            = 'No end date';
$string['editnotification']             = 'Edit this notification';
$string['deletenotification']           = 'Delete this notification';
$string['defaultnotificationdesc']      = 'No Description';
$string['defaultnotificationmsg']       = 'Your message here';
$string['calendarselectstart']          = 'Select a start date';
$string['calendarselectend']            = 'Select an end date';
$string['noenddate']                    = 'No end date';
$string['courseselectinstr']            = 'You can move multiple courses by holding down the Ctrl key';
$string['successfulupdate']             = 'Notification was successfully updated!';
$string['failedupdate']                 = 'There was an error updating the notification.';
$string['confirmnotificationdelete']    = 'Are you sure you want to delete this notification?';

$string['displaytabsall']                 = 'All Courses';
$string['displaytabssplit']               = 'Enrolled/Not Enrolled';
$string['displaytabsenrolledonly']        = 'Enrolled Only';
$string['splitallcourses']                = 'Other courses view.';
$string['splitallcourses_def']            = 'Specifies how to tabulate all courses, either show all, or split by whether the student is enrolled in a course';

$string['lp_course_progress:managenotifications'] = 'Allow management of incomplete course notifications';
$string['lp_course_progress:addinstance'] = 'Add a new Course Progress block';
$string['lp_course_progress:myaddinstance'] = 'Add a new Course Progress block to My Home';
$string['undefinedmoduleinstance'] = "Module instance id not found for course progress object.";
$string['progressnocompletes'] = 'You have not completed any learning yet. Please check the incomplete tab to see your active learning.';
$string['progressnoincompletes'] = 'No incomplete learning at the moment. Check the not-attempted tab, there may be some learning waiting for you.';
$string['progressnononattempts'] = 'Either you have not enrolled in any courses or you have already started some learning. Please check the incomplete tab to see your active learning.';


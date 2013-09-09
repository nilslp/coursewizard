<?php
/*
 * Settings page for lp_course_progress
 * @copyright Learning Pool 2012
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lp_course_progress
 */

global $CFG;
require_once($CFG->dirroot.'/blocks/lp_course_progress/lib.php');

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Define an array for holding the quiz options
    $score_display_options = array(
        LP_COURSE_PROGRESS_SCOREOPTION_NONE => get_string('settingscoreoptionnone', 'block_lp_course_progress'),
        LP_COURSE_PROGRESS_SCOREOPTION_SCORE_ONLY => get_string('settingscoreoptionscoreonly', 'block_lp_course_progress'),
        LP_COURSE_PROGRESS_SCOREOPTION_ALL => get_string('settingscoreoptionall', 'block_lp_course_progress')
    );
    
    $tab_display_options = array(
        LP_DISPLAY_COURSE_TABS_ALL => get_string('displaytabsall','block_lp_course_progress'),
        LP_DISPLAY_COURSE_TABS_SPLIT => get_string('displaytabssplit','block_lp_course_progress'),
        LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY => get_string('displaytabsenrolledonly','block_lp_course_progress')
    );

    // Indicate if all courses (enrolled an otherwise) should be displayed
    /*$settings->add(new admin_setting_configcheckbox('block_lp_course_progress/showallcourses', get_string('showallcourses', 'block_lp_course_progress'), 
                get_string('showallcourses_def', 'block_lp_course_progress'), 0));*/
    
    // Indicate if the ordering of the modules should be as per the DLE
    $settings->add(new admin_setting_configcheckbox('block_lp_course_progress/persistordering', get_string('persistordering', 'block_lp_course_progress'), 
                get_string('persistordering_def', 'block_lp_course_progress'), 0));
    
    // Indicate the formatting for quiz scores
    $settings->add(new admin_setting_configselect('block_lp_course_progress/scoremessagedisplayoption', get_string('scoremessagedisplayoption', 'block_lp_course_progress'), 
                get_string('scoremessagedisplayoption_def', 'block_lp_course_progress'), LP_COURSE_PROGRESS_SCOREOPTION_ALL, $score_display_options));
    
    // Indicate how to display all courses
    $settings->add(new admin_setting_configselect('block_lp_course_progress/splitallcourses', get_string('splitallcourses', 'block_lp_course_progress'), 
                get_string('splitallcourses_def', 'block_lp_course_progress'), LP_DISPLAY_COURSE_TABS_SPLIT, $tab_display_options));
    
    // filter courses with no course id?
    $settings->add(new admin_setting_configcheckbox('block_lp_course_progress/filternocourseid', get_string('filternocourseid', 'block_lp_course_progress'), 
                get_string('filternocourseid_def', 'block_lp_course_progress'), 0));
    
    // override activities where user has marked themselves as completed?
    $settings->add(new admin_setting_configcheckbox('block_lp_course_progress/overrideselfcomplete', get_string('overrideselfcomplete', 'block_lp_course_progress'), 
                get_string('overrideselfcomplete_def', 'block_lp_course_progress'), 0));
}



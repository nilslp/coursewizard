<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

global $USER;

admin_externalpage_setup('globalcourseaccessmanagersettings');

$returnurl = $CFG->wwwroot."/local/lp_courseaccessmanager/globalsettings.php";

// form definition
$mform = new local_lp_courseaccessmanager_global_settings_form();

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

echo $OUTPUT->header();

$fromform = $mform->get_data();
if ($fromform) {

    if(empty($fromform->submitbutton)) {
        echo $OUTPUT->notification(get_string('unrecognizedaction','local_lp_courseaccessmanager'), 'notifyfailure');
    }

    if(update_global_settings($fromform)) {
        echo $OUTPUT->notification(get_string('successfulupdate','local_lp_courseaccessmanager'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('unsuccessfulupdate','local_lp_courseaccessmanager'), 'notifyfailure');
    }
}

echo $OUTPUT->heading(get_string('pluginname','local_lp_courseaccessmanager'));

// display the form
$mform->display();

echo $OUTPUT->footer();

/**
 * Update global settings
 *
 * @param object $fromform Moodle form object containing global setting changes to apply
 *
 * @return boolean True if settings could be successfully updated
 */
function update_global_settings($fromform) {
    
    $success = false;
    
    if (isset($fromform->defaultview)) {
        $defaultview = $fromform->defaultview;
        $success = set_config('defaultview', $defaultview, 'local/lp_courseaccessmanager');
    }    
    
    if (isset($fromform->forceloginforcoursesearch)) {        
        $success = set_config('forceloginforcoursesearch', $defaultview, 'local/lp_courseaccessmanager');
    }
    
    return $success;
}


?>

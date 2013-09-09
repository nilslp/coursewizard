<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');

global $USER;

$context = context_system::instance();

require_capability('moodle/site:config', $context);

admin_externalpage_setup('globalreportsettings');

$returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/globalsettings.php";

// form definition
$mform = new report_builder_global_settings_form();

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

echo $OUTPUT->header();

if ($fromform = $mform->get_data()) {

    if(empty($fromform->submitbutton)) {
        echo $OUTPUT->notification(get_string('error:unknownbuttonclicked','block_lp_reportbuilder'), 'notifyfailure');
    }

    if(update_global_settings($fromform)) {
        echo $OUTPUT->notification(get_string('globalsettingsupdated','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('error:couldnotupdateglobalsettings','block_lp_reportbuilder'), 'notifyfailure');
    }
}

print_container_start(true, 'reportbuilder-navbuttons');
echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/index.php', get_string('allreports','block_lp_reportbuilder'));
print_container_end();

echo $OUTPUT->heading(get_string('reportbuilderglobalsettings','block_lp_reportbuilder'));

// display the form
$mform->display();

echo $OUTPUT->footer();

/**
 * Update global report builder settings
 *
 * @param object $fromform Moodle form object containing global setting changes to apply
 *
 * @return boolean True if settings could be successfully updated
 */
function update_global_settings($fromform) {
    global $REPORT_BUILDER_EXPORT_OPTIONS;

    // Initialisation
    $exportoptions = 0;
    $defaultreportpermissions = 0;
    $allowdeletedusers = 0;
    $showcoursesearchhierarchyfilter = 0;
    
    foreach($REPORT_BUILDER_EXPORT_OPTIONS as $option => $code) {
        $checkboxname = 'export' . $option;
        if(isset($fromform->$checkboxname) && $fromform->$checkboxname == 1) {
            $exportoptions += $code;
        }
    }
    
    if (isset($fromform->defaultreportpermissions)) {
        $defaultreportpermissions = $fromform->defaultreportpermissions;
    }
    
    set_config('defaultreportpermissions', $defaultreportpermissions, 'reportbuilder');

    if (isset($fromform->allowdeletedusers)) {
        $allowdeletedusers = $fromform->allowdeletedusers;
    }
    
    set_config('allowdeletedusers', $allowdeletedusers, 'reportbuilder');
    
    if (isset($fromform->defaultdateformat)) {
        set_config('defaultdateformat', $fromform->defaultdateformat, 'reportbuilder');
    }
    
    if (isset($fromform->defaultdatetimeformat)) {
        set_config('defaultdatetimeformat', $fromform->defaultdatetimeformat, 'reportbuilder');
    }
    
    if (isset($fromform->defaulttimeformat)) {
        set_config('defaulttimeformat', $fromform->defaulttimeformat, 'reportbuilder');
    }
    
    if (isset($fromform->showcoursequizquick)) {
        set_config('showcoursequizquick', $fromform->showcoursequizquick, 'reportbuilder');
    }    
    
    if (isset($fromform->extrareportfields)) {
        set_config('extrareportfields', $fromform->extrareportfields, 'reportbuilder');
    }
    
    if (isset($fromform->showcustomtrentdownload)) {
        set_config('showcustomtrentdownload', $fromform->showcustomtrentdownload, 'reportbuilder');
    }
    
    if (isset($fromform->coursequizquickformat)) {
        set_config('coursequizquickformat', $fromform->coursequizquickformat, 'reportbuilder');
    }
    
    if (isset($fromform->includemodulename)) {
        set_config('includemodulename', $fromform->includemodulename, 'reportbuilder');
    }
        
    if (isset($fromform->showcoursesearchhierarchyfilter)) {
       $showcoursesearchhierarchyfilter = $fromform->showcoursesearchhierarchyfilter;
    } 
    
    set_config('showcoursesearchhierarchyfilter', $showcoursesearchhierarchyfilter, 'reportbuilder');
    
    if (isset($fromform->enableskillschecker)) {
        set_config('enableskillschecker', $fromform->enableskillschecker, 'reportbuilder');
    }
    
    if (isset($fromform->skillscheckscorm)) {
        set_config('skillscheckscorm', $fromform->skillscheckscorm, 'reportbuilder');
    }
    
    if (isset($fromform->skillschecksco)) {
        set_config('skillschecksco', $fromform->skillschecksco, 'reportbuilder');
    }
    
    if (isset($fromform->skillscheckstartmonth)) {
        set_config('skillscheckstartmonth', $fromform->skillscheckstartmonth, 'reportbuilder');
    }

	if (isset($fromform->runhistoriccron)) {
		set_config('runhistoriccron',$fromform->runhistoriccron,'block_lp_reportbuilder');
		set_config('coursecompletionprocessed',0,'reportbuilder');
	}
        
    if (isset($fromform->usesimplehierarchyselect)) {
        set_config('usesimplehierarchyselect',$fromform->usesimplehierarchyselect,'block_lp_reportbuilder');
    }
    
    return set_config('exportoptions', $exportoptions, 'reportbuilder');
}


?>

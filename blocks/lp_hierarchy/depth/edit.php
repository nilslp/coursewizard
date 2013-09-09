<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_form.php");

require_login();

// Retrieve the ID from the query string
$id = optional_param('id', 0, PARAM_INT);    

$PAGE->set_context(build_context_path());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_hierarchy/depth/edit.php');
$PAGE->set_pagelayout('base');

if ($id == 0) {
    $PAGE->set_title(get_string('adddepthtitle', 'block_lp_hierarchy'));
    $PAGE->set_heading(get_string('adddepthtitle', 'block_lp_hierarchy'));
} 
else {
    $PAGE->set_title(get_string('updatedepthtitle', 'block_lp_hierarchy'));
    $PAGE->set_heading(get_string('updatedepthtitle', 'block_lp_hierarchy'));
}

$settings_url = new moodle_url('/admin/settings.php?section=blocksettinglp_hierarchy');
$manage_feeds = new moodle_url('/blocks/lp_hierarchy/manage_hierarchy.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_lp_hierarchy'), $settings_url);
$PAGE->navbar->add(get_string('manage_hierarchy', 'block_lp_hierarchy'), $manage_feeds);

$hierarchy = Hierarchy::get_instance();
$context = get_context_instance(CONTEXT_SYSTEM);

if ($id == 0) {
    // Creating new depth level
    //require_capability('moodle/local:create'.$type.'depth', $context);

    $depth = new object();
    $depth->id = 0;

    // Calculate next depth level
    $depth->level = $hierarchy->get_next_depth_level();

    if ($CFG->block_lp_hierarchy_depth < $depth->level) {
    	error(get_string('e_depth_reached', 'block_lp_hierarchy'));
    }

    if (!$depth->level) {
        $depth->level = 1;
    }
} 
else {
    // editing existing depth level
    //require_capability('moodle/local:update'.$type.'depth', $context);
            
    if (!$depth = $hierarchy->get_depth_by_id($id)) {
        error(get_string('e_depth_select', 'block_lp_hierarchy'));
    }
    
    if ($CFG->block_lp_hierarchy_depth < $depth->level) {
        error(get_string('e_depth_reached', 'block_lp_hierarchy'));
    }
}

// create form
$datatosend = array('type'=>0);
$depthform  = new depth_edit_form(null, $datatosend);
$depthform->set_data($depth);

if ($depthform->is_cancelled()){
	// User clicked 'Cancel'
    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
} 
else if ($newdepth = $depthform->get_data()) {
    // Update data
    $newdepth->timemodified = time();
    $newdepth->modifierid = $USER->id;

    if ($newdepth->id == 0) {
    	// This is a new depth level
        unset($newdepth->id);
		
        $newdepth->timecreated = time();
        $newdepth->level = $hierarchy->get_next_depth_level();

        if (!$newdepth->id = $DB->insert_record('lp_hierarchy_depth', $newdepth)) {
            error(get_string('e_depth_insert', 'block_lp_hierarchy'));
        }
    } 
    else {
    	// This is an existing depth level
        if (!$DB->update_record('lp_hierarchy_depth', $newdepth)) {
            error(get_string('e_depth_update', 'block_lp_hierarchy'));
        }
    }

    // Reload from database
    $newdepth = $DB->get_record('lp_hierarchy_depth', array ('id' => $newdepth->id));

    // Re-evaluate the generic 'Directorate / Department / Sub-Department' label
    // on the user profile page
    $hierarchy->reset_profile_fieldset_label();
    							
    // Return to the landing page
    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
}

echo $OUTPUT->header();

/// Finally display the form
$depthform->display();

echo $OUTPUT->footer();
?>
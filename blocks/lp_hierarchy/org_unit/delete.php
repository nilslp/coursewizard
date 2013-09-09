<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("delete_form.php");

require_login();

// Get the query string
$id = required_param('id', PARAM_INT);

$PAGE->set_context(build_context_path());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_hierarchy/depth/delete.php', array('id' => $id));
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('deleteorgunittitle', 'block_lp_hierarchy'));
$PAGE->set_title(get_string('deleteorgunittitle', 'block_lp_hierarchy'));

// Build the breadcrumb
$settings_url = new moodle_url('/admin/settings.php?section=blocksettinglp_hierarchy');
$manage_hierarchy = new moodle_url('/blocks/lp_hierarchy/manage_hierarchy.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_lp_hierarchy'), $settings_url);
$PAGE->navbar->add(get_string('manage_hierarchy', 'block_lp_hierarchy'), $manage_hierarchy);
echo $OUTPUT->header();  

$hierarchy = Hierarchy::get_instance();
$context = get_context_instance(CONTEXT_SYSTEM);
    
if ($id == 0) {
    // Creating new depth level
    //require_capability('moodle/local:create'.$type.'depth', $context);
    $org = new object();
    $org->id = 0;
    $org->parentid = 0;
} 
else {
    if (!$org = $hierarchy->get_org_unit_by_id($id)) {
        error(get_string('e_org_unit_select', 'block_lp_hierarchy'));
    }
}

// Create the form
$datatosend = array('item'=>$org);

$orgform  = new org_unit_delete_form(null, $datatosend);
$orgform->set_data($org);

if ($orgform->is_cancelled()){
    // User clicked 'Cancel'
    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
} 
else if ($neworg = $orgform->get_data()) {
    if ($neworg->id != 0) {
        $hierarchy->delete_org_unit($neworg->id);
    }
		
    // Return to the landing page
    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
}

/// Finally display the form
$orgform->display();

echo $OUTPUT->footer();
?>
<?php
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $maximum_depth = 3; // Change this to change the maximum hierarchy depth
    $hierarchy = Hierarchy::get_instance();

    // Get the maximum hierarchy depth
    $max_depth = $hierarchy->get_max_depth();

    // Get the view of all the hierarchy data
    $hierarchy->make_hierarchy_list_for_filter($hierarchy_data, null, true, false, '', null, true);
    
    // If there are no depths set up, allow all of them
    // Otherwise, don't allow the depth to decrease while there are depth levels
    $start_value = ($max_depth == 0)? 0 : $max_depth;

    // To preserve the indexing, set the first item in the array
    $depth_options = array($start_value => $start_value);

    // Build the rest of the array
    $start_value = $start_value + 1;

    for ($i = $start_value; $i <= $maximum_depth; $i++) {
        array_push($depth_options, $i);
    }

    // Render the form
    // - The number of levels in this DLE
    $settings->add(new admin_setting_configselect('block_lp_hierarchy_depth', get_string('hierarchy_depth', 'block_lp_hierarchy'), 
                get_string('hierarchy_depth_def', 'block_lp_hierarchy'), 1, $depth_options));

    // - If the DLE should allow free-text departments/sub-departments, etc.
    $settings->add(new admin_setting_configcheckbox('block_lp_hierarchy_allow_freetext', get_string('allow_freetext', 'block_lp_hierarchy'), 
                get_string('allow_freetext_def', 'block_lp_hierarchy'), 0));

    // - If users can edit their hierarchy
    $settings->add(new admin_setting_configcheckbox('block_lp_hierarchy_allow_user_edit', get_string('allow_user_edit', 'block_lp_hierarchy'),
                get_string('allow_user_edit_def', 'block_lp_hierarchy'), 0));
    
    $settings->add(new admin_setting_configselect('block_lp_hierarchy_excluded', get_string('hierarchy_to_exclude', 'block_lp_hierarchy'),
                get_string('hierarchy_to_exclude_def', 'block_lp_hierarchy'), 0, $hierarchy_data));
    
	// - If the DLE should allow free-text departments/sub-departments, etc.
    $settings->add(new admin_setting_configcheckbox('block_lp_hierarchy_buyways', get_string('allow_buyways', 'block_lp_hierarchy'), 
                get_string('allow_buyways_def', 'block_lp_hierarchy'), 0));
    
    $settings->add(new admin_setting_configtext('block_lp_hierarchy_restrict_from_signup_list', get_string('restrict_from_signup_list', 'block_lp_hierarchy'), get_string('restrict_from_signup_list_def', 'block_lp_hierarchy'), ''));
    
    // Add a link to allow the user to configure the hierarchy
    $link = '<a href="'.$CFG->wwwroot.'/blocks/lp_hierarchy/manage_hierarchy.php">'.get_string('manage_hierarchy', 'block_lp_hierarchy').'</a>';

    $settings->add(new admin_setting_heading('block_lp_hierarchy_addheading', '', $link));
}

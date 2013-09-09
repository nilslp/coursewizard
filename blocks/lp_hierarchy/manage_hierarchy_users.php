<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("manage_hierarchy_users_edit_form.php");

global $USER;
require_login();
if ($USER->username == 'guest') {
    // Prevent Guest users from seeing this page
    print_error('accessdenied', 'admin');
    die;
}
// Editing param
$edit = optional_param('edit', -1, PARAM_BOOL);
if ($edit === -1) {
    // If the user has navigated across pages
    // persist the editing
    if (isset($USER->hierarchyediting)) {
        if ($USER->hierarchyediting == 1) {
            $edit = 1;
        } else {
            $edit = 0;
        }
    } else {
        $edit = 0;
    }
}

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_hierarchy/manage_hierarchy_users.php');
$can_edit = has_capability('block/lp_hierarchy:manage', $PAGE->context);
// Define the page layout and header/breadcrumbs
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_hierarchy_users', 'block_lp_hierarchy'));
$PAGE->set_heading(get_string('manage_hierarchy_users', 'block_lp_hierarchy'));
$hierarchy = Hierarchy::get_instance();

//javascript
$jsconfig = array(
	'name' => 'user_swapper',
	'fullpath' => '/blocks/lp_hierarchy/javascript/user_swapper.js',
	'requires' => array(
                        'node',
                        'event',
                        'selector-css3',
                        'event-hover',
                        'io',
                        'json-parse'
                    ),
    'strings'  => array(

        array('nochanges' , 'block_lp_hierarchy'),
        array('discard' ,'block_lp_hierarchy'),
        array('makechoice', 'block_lp_hierarchy'),
        array('nolist' , 'block_lp_hierarchy'),
        array('lp_ajaxsuccess' ,'block_lp_hierarchy'),
        array('search' ,'block_lp_hierarchy'),
        array('lp_ajaxerror', 'block_lp_hierarchy')
    )
);

$PAGE->requires->js_init_call('M.block_lp_hierarchy_users.init', null, false, $jsconfig);

$settings_url = new moodle_url('/admin/settings.php?section=blocksettinglp_hierarchy');
$manage_hierarchy_users = new moodle_url('/blocks/lp_hierarchy/manage_hierarchy_users.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_lp_hierarchy'), $settings_url);
$PAGE->navbar->add(get_string('manage_hierarchy_users', 'block_lp_hierarchy'), $manage_hierarchy_users);
echo $OUTPUT->header();
if ($can_edit) {
    echo '<div class="navbutton">';
    echo $hierarchy->get_editing_button($edit);
    echo '</div>';
}
// Retrieve the depths that have been defined
$depths = $hierarchy->get_depths();
if (!$depths) {
    // No depths have been set up yet
    // At least one depth must be setup before the hierarchy can be managed
    echo '<div>' . get_string('no_depth_defined', 'block_lp_hierarchy') . '</div>';
    if ($USER->hierarchyediting === 1 && $can_edit) {
        echo $OUTPUT->single_button($PAGE->url, get_string('add_depth_button', 'block_lp_hierarchy'), 'GET');
    }
    echo $OUTPUT->footer();
    exit();
}
echo '<h2 class="main">Manage Hierarchy Users</h2>';
echo '<div class="group yui3-u-1-3">';
echo '<h2>' . get_string('depth_summary_title', 'block_lp_hierarchy') . '</h2>';
// Start displaying the org depths
echo '<ul>';
foreach ($depths as $depth) {
    echo '<li>';
    if (isset($USER->hierarchyediting) && $USER->hierarchyediting === 1) {
        // Add a link to edit
        echo sprintf("<a href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/depth/edit.php?id=%s\">%s</a>", $depth->id, htmlentities($depth->fullname));
    } else {
        echo htmlentities($depth->fullname);
    }
    if ($CFG->block_lp_hierarchy_allow_freetext && $depth->level == $CFG->block_lp_hierarchy_depth) {
        echo "*";
    }
    echo '</li>';
}
// End displaying the org depths (close the <ul> tag)
echo '</ul>';
if ($CFG->block_lp_hierarchy_allow_freetext) {
    echo '<div>*&nbsp;' . get_string('freetext_enabled', 'block_lp_hierarchy') . '</div>';
}
if ($CFG->block_lp_hierarchy_depth && ($CFG->block_lp_hierarchy_depth >= $hierarchy->get_next_depth_level()) && (isset($USER->hierarchyediting) && $USER->hierarchyediting === 1) && $can_edit) {
    echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/depth/edit.php', get_string('add_depth_button', 'block_lp_hierarchy'), 'GET');
}
echo "</div>";
echo '<div class="group yui3-u-2-3">';
// Retrieve the organisation hierarchy
$organisations = $hierarchy->get_hierarchy();
// Initialise the level to zero
$level = 0;
if ($depths) {
    echo '<h2>' . get_string('hierarchy_title', 'block_lp_hierarchy') . '</h2>';
    if (!$organisations) {
        echo '<p>' . get_string('no_org_units', 'block_lp_hierarchy') . '</p>';
    }
    if ($edit === 1 && $can_edit) {
        echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/org_unit/edit.php', get_string('add_org_unit_button', 'block_lp_hierarchy'), 'GET');
    }
}
echo "</div>";
$orgform = new manage_hierarchy_users_edit_form(null, null, 'post', null, array('class'=>'manage-hierarchy'));
$orgform->display();
echo $OUTPUT->footer();
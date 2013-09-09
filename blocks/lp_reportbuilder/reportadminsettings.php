<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');

global $USER;

require_login();

$search_term = optional_param('search', '', PARAM_ALPHANUM);

admin_externalpage_setup('reportadminsettings');

$returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/reportadminsettings.php";

require_capability('block/lp_reportbuilder:configurepermissions', get_context_instance(CONTEXT_SYSTEM));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('reportadminconfig','block_lp_reportbuilder') . '&nbsp;' . $OUTPUT->help_icon('configurepermissions', 'block_lp_reportbuilder'));

// Get a list of user IDs who have 'viewreports' permissions and match any 
// search term passed in
$report_users = reportbuilder_get_report_users($search_term);

if ($report_users) {    
    // Output the search form
    echo "<form id='usersearch_form' name='usersearch_form' action='reportadminsettings.php' method='post'>";
    echo get_string('searchusers', 'block_lp_reportbuilder');
    echo '&nbsp;<input type="text" id="search" name="search" value="' . stripslashes($search_term) . '" />';
    echo "  <input type='submit' value='" . get_string('search') . "' />";
    echo "<input type='button' onclick='window.location=\"$returnurl\"' value='" . get_string('cancel') . "' />";
    echo "</form>";

    $hierarchy = Hierarchy::get_instance();
    
    $hierarchy_label = $hierarchy->get_hierarchy_field_label_text(true);
    
    $data = array();
    
    // Generate the list display table
    $tableheader = array(get_string('lastname'),
                         get_string('firstname'),
                         get_string('email'),
                         $hierarchy_label,
                        get_string('options','block_lp_reportbuilder'));
    
    foreach ($report_users as $user) {
        $cells = array();
        
        $cells[] = new html_table_cell($user->lastname);           
        $cells[] = new html_table_cell($user->firstname);
        $cells[] = new html_table_cell($user->email);
        $cells[] = new html_table_cell($user->hierarchy);
        $cells[] = new html_table_cell("<a href='" . $CFG->wwwroot . 
                '/blocks/lp_reportbuilder/editpermissions.php?userid=' . $user->id . "' " .
                'title="' . get_string('editpermission', 'block_lp_reportbuilder') . '">' .
                '<img src="'.$CFG->wwwroot.'/pix/t/edit.gif" alt="' .
                get_string('editpermission', 'block_lp_reportbuilder') . '" /></a>');

        $row = new html_table_row($cells);          

        $data[] = $row;
    }
    
    // Output the user generated report table
    $reportstable = new html_table();
    $reportstable->summary = '';
    $reportstable->head = $tableheader;
    $reportstable->data = $data;
        
    echo html_writer::table($reportstable);
}
else {
    // No results found
    echo get_string('noresultsfound', 'block_lp_reportbuilder');
    echo '<br /><br />';
    
    echo get_string('configurepermissions_help', 'block_lp_reportbuilder');
}

// Only display the link to users who can actually assign to the role
if (has_capability('moodle/role:assign', get_context_instance(CONTEXT_SYSTEM))) {
    // Create the button to link to the report_admin role
    echo reportbuilder_get_link_to_report_admin();
}

echo $OUTPUT->footer();
?>
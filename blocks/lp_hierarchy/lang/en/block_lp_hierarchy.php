<?php

/**
 * Strings for component 'block_lp_hierarchy', language 'en'
 *
 * @package   block_lp_hierarchy
 * @copyright 2011 onwards Learning Pool{@link http://www.learningpool.com}
 */
$string['pluginname'] = 'Learning Pool - Hierarchy';
$string['label_hierarchy_depth'] = 'Hierarchy Depth';
$string['label_hierarchy_max_depth'] = 'Maximum Depth';
$string['label_use_freetext'] = 'Use free-text';
$string['freetext_enabled'] = 'This depth is a free-text field that can only be edited in the user profile';

$string['allow_user_edit'] = 'Allow users to edit hierarchy';
$string['allow_user_edit_def'] = 'This option will allow users to edit their own hierarchy from their profile page';
$string['allow_freetext'] = 'Allow free-text departments';
$string['allow_freetext_def'] = 'This option will allows storing free-text departments for a user (not recommended as it is not possible to report on these accurrately)';
$string['hierarchy_to_exclude'] = 'Excluded hierarchy';
$string['hierarchy_to_exclude_def'] = 'This option will exclude the specified hierarchy from various plugins, e.g. Report Builder, Course Enrolment Manager, etc.';
$string['restrict_from_signup_list'] = 'Org IDs to exclude from signup';
$string['restrict_from_signup_list_def'] = 'Enter a comma-separated list of Org ID values here to hide them from the signup form';
$string['manage_hierarchy'] = 'Manage Hierarchy';
$string['manage_hierarchy_users'] = 'Manage Hierarchy Users';

$string['nochanges'] = 'No changes were made.';
$string['discard'] = "Are you sure you want to discard unsaved changes?";
$string['makechoice'] = "You must choose different hierarchies.";
$string['nolist'] = 'Unable to retrieve list of users.';
$string['lp_ajaxsuccess'] = "Your changes were made successfully.";
$string['lp_ajaxerror'] = "An error occurred.";
$string['search'] = "Search";
$string['source'] = "Source";
$string['target'] = "Target";

$string['hierarchy_depth'] = 'Hierarchy depth';
$string['hierarchy_depth_def'] = 'The number of configurable levels in this Moodle instance';
$string['no_depth_defined'] = 'There are currently no depths defined.  At least one depth must be defined in order to manage the hierarchy.';
$string['add_depth_button'] = 'Add Depth';
$string['depth_summary_title'] = 'Depth summary';
$string['hierarchy_title'] = "Hierarchy";
$string['top'] = '[Top]';

$string['adddepthtitle'] = 'Add Depth';
$string['updatedepthtitle'] = 'Update Depth';
$string['depthlevel'] = 'Depth level';
$string['fullnamedepth'] = 'Depth level full name';
$string['missingfullnamedepth'] = 'Depth level full name is required';
$string['shortnamedepth'] = 'Depth level short name';
$string['missingshortnamedepth'] = 'Depth level short name is required';

$string['addorgunittitle'] = 'Add Org Unit';
$string['editorgunittitle'] = 'Edit Org Unit';
$string['deleteorgunittitle'] = 'Delete Org Unit';
$string['no_org_units'] = 'There are currently no org units defined.';
$string['add_org_unit_button'] = 'Add Org Unit';
$string['manage_users_button'] = 'Manage Users';
$string['parentorgunit'] = 'Parent';
$string['fullnameorg'] = 'Org unit full name';
$string['missingfullnameorg'] = 'Org unit full name is required';
$string['shortnameorg'] = 'Org unit short name';
$string['missingshortnameorg'] = 'Org unit short name is required';
$string['leadcontact'] = 'Contact';
$string['visible'] = 'Visible';
$string['deleteorguserswarningplural'] = "There are %d users associated with this org unit, so it cannot be deleted.";
$string['deleteorguserswarning'] = "There is a user associated with this org unit, so it cannot be deleted.";
$string['deleteorgwarningplural'] = "There are %d org units beneath this org unit, so it cannot be deleted.";
$string['deleteorgwarning'] = "There is an org unit beneath this org unit, so it cannot be deleted.";
$string['select_hierarchy'] = '{$a} is a required field';

$string['edittooltip'] = 'Edit';
$string['deletetooltip'] = 'Delete';
$string['confirmdelete'] = 'Confirm Delete';

// Capabilities
$string['lp_hierarchy:delete'] = 'Delete depths and organisational units in a hierarchy';
$string['lp_hierarchy:manage'] = 'Manage a hierarchy';
$string['lp_hierarchy:addinstance'] =  'Add a new Hierarchy block';
$string['lp_hierarchy:myaddinstance'] =  'Add a new Hierarchy block to My Home';

// Errors
$string['e_depth_reached'] = 'The configured hierarchy depth has already been reached';
$string['e_depth_insert'] = 'Error creating new depth record';
$string['e_depth_update'] = 'Error updating depth record';
$string['e_depth_select'] = 'A depth with the specified ID could not be found';

$string['e_org_unit_parent_incorrect'] = "Parent ID was incorrect";
$string['e_org_unit_insert'] = 'Error creating new org unit';
$string['e_org_unit_update'] = 'Error updating org unit';
$string['e_org_unit_edit_generic'] = 'Error editing org unit. Are you sure your Org unit shortname is unique?';
$string['e_org_unit_select'] = 'An org unit with the specified ID could not be found';
$string['error:cannothideorgunit'] = 'Unable to hide this org unit as there are {$a} user(s) affected';

//Buyways
$string['allow_buyways'] = 'Buyways authentication';
$string['allow_buyways_def'] = 'Buyways authentication setting';
$string['buyways_checking'] = 'Checking you school id <img src="../pix/y/loading.gif"/>';
$string['buyways_checking_error'] = 'Incorrect id - Please contact your bursar or business manager';
$string['buyways_checking_fail'] = 'Could not reach the server, please refresh the page and try again';
$string['buyways_checking_error_nolvl'] = '{$a} not selected';
$string['buyways_checking_error_nosid'] = 'School id not entered';
$string['buyways_checking_error_nomch'] = 'Entered school id and selected {$a} do not match';
$string['buyways_checking_error_prob'] = 'Could not validate entered details';

// Manage Users Form
$string['label:search'] = 'Filter Users';
$string['label:column_a'] = 'Column A';
$string['label:column_b'] = 'Column B';
$string['label:move_user_atob'] = 'Move Selected User From Column A to Column B.';
$string['label:move_user_btoa'] = 'Move Selected User From Column B to Column A.';
$string['label:move_allusers_atob'] = 'Move All Users From Column A to Column B.';
$string['label:move_allusers_btoa'] = 'Move All Users From Column B to Column A.';
$string['label:savechanges'] = 'Save Changes';
$string['label:revert'] = 'Revert';
$string['text:footnote'] = '<h3>Quick Tips</h3><ul><li>Deleted users are shown in <b>bold text</b>.</li><li>Click to select a user from a list to move.</li><li>Hold <b>Ctrl (or Cmd-&#8984;)</b> when clicking to select more than one user.</li></ul>';

<?php
/**
 * language file: en
 */

// headings etc
$string['pluginname']               = 'Course Access Manager';
$string['categoryname']             = 'Course Access'; 
$string['courseaccessmanager']      = 'Manage Access'; 
$string['backtostart']              = 'Return to Course List'; 
$string['courselistheader']         = 'Categories / Courses'; 
$string['rulelistheader']           = 'Current Access Settings';
$string['userlistheader']           = 'Current Users';
$string['tabhierarchy']             = 'Organisational Hierarchy';
$string['tabindividual']            = 'Individual Students';
$string['summaryheader']            = 'Access Summary';
$string['formsearchusers']          = 'Search Users';
$string['totalusers']               = 'Total Users';
$string['totalusersfound']          = 'Total Matching Users';
$string['availableusersfound']      = 'Matching Available Students';
$string['grantgroup']               = 'Grant Access - Group';
$string['grantindividual']          = 'Grant Access - Individual';
$string['clearsearchinput']         = 'Clear';
$string['searchsubmit']             = 'Search';
$string['btnnewrule']               = '[+] Add New Rule';
$string['defaultruletitle']         = 'Rule#{$a->id}';
$string['btnnewrule']               = '[+] Add New Rule';
$string['grantresultheader']        = 'Course Access Updated';
$string['globalsettings']           = 'Settings'; 
$string['defaultview']              = 'Default Course View';
$string['configdefaultview']        = 'If a course has no access rule, will it show or not';
$string['allcourses']               = 'Show Unmanaged Courses';
$string['owncourses']               = 'Hide Unmanaged Courses';

// message text
$string['loading']                  = 'Loading ... '; 
$string['dberror']                  = 'The was an error writing to the database: {$a}'; 
$string['unrecognizedaction']       = 'Failed to perform requested action.'; 
$string['undefinedrule']            = 'You need to specify at least one course or category and at least one hierarchy level or user.'; 
$string['createrulefailed']         = 'Failed to create a new rule!'; 
$string['createrulesucceeded']      = 'Successfully created the rule {$a}!'; 
$string['invalidrule']              = 'No valid entry found for that rule.'; 
$string['confirmruledelete']        = 'Are you sure you want to delete this rule?'; 
$string['successfulupdate']         = 'Successful Update!';
$string['unsuccessfulupdate']       = 'Update was unsuccessful!';
$string['nocoursesfound']           = 'No Courses were found.'; 
$string['unknowncategory']          = 'Unknown Category.';
$string['pleaseselectcourse']       = 'Please select a course.';
$string['hierarchynotinstalled']    = 'Learning Pool Hierarchy block is not installed!';
$string['pleaseselecthierarchy']    = 'Please select one or more departments!';
$string['grantingnumusers']         = 'You are about to grant access to {num_users} student(s).';
$string['numusersgranted']          = '{num_users} user(s) were granted access.'; 
$string['lp_courseaccessmanager:manageaccess'] = 'Mange course access settings';
$string['accessdenied']             = 'Access Restricted';
$string['accessdeniedmessage']      = 'Access rules are in place for this course. Please contact your administrator.';

$string['forceloginforcoursesearch'] = 'Force Login for Course Search';
$string['forceloginforcoursesearch_help'] = '<p>If enabled, users are required to login to search courses.</p>';
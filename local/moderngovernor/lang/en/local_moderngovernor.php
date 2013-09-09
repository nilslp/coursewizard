<?php

$string['pluginname'] = 'Modern Governor Admin';
$string['settings'] = 'Settings';
$string['admin'] = 'School Administration';
$string['menuitem'] = 'Modern Governor';
$string['schoolname'] = 'School';
$string['select'] = 'Select';
$string['leaname'] = 'LEA';
$string['enabled'] = 'LIVE';
$string['disabled'] = 'DEACTIVATED';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['confirmuser'] = 'Confirm Account';
$string['resetuserpass'] = 'Reset Password';
$string['confirmed'] = 'Confirmed';
$string['unconfirmed'] = 'Not Confirmed';
$string['confirmconfirm'] = 'Are you sure you want to confirm this user? (Email is {$a})';
$string['confirmreset'] = 'Are you sure you want to reset this user\'s password? (Email is {$a})';
$string['confirmenable'] = 'Are you sure you want to enable this school? ({$a})';
$string['confirmdisable'] = 'Are you sure you want to disable this school? ({$a})';
$string['confirmcombine'] = 'Are you sure you combine the following schools?
    {$a}';
$string['status'] = 'Status';
$string['noresults'] = 'No matches';
$string['settingsupdated'] = 'Settings were updated!';
$string['loading'] = 'Loading ...';
$string['xrecords'] = '{a} School(s) Found';
$string['adminusers'] = 'User Administration';
$string['adddemoaccount'] = 'Add Demo Account';
$string['userdetails'] = 'User Details';
$string['noschoolsselected'] = '<em>No schools selected</em>';
$string['schooladmin'] = 'Administrate Schools';
$string['combineschools'] = 'Combine Schools';
$string['notenoughschools'] = 'You need to select at least 2 schools to combine!';
$string['noschoolnamespecified'] = 'You need to specify a new school name!';
$string['nonewleaspecified'] = 'You need to select a target LEA for the combined school!';
$string['combine'] = 'Combine';
$string['combinex'] = '<em>{$a} school(s) selected</em>';
$string['combine_help'] = 'Select schools using the checkboxes below and then click this button to confirm and combine them into one new school.';
$string['synchierarchy'] = 'Synchronise Hierarchy';
$string['synchierarchy_help'] = 'This setting determines whether hierarchy information is synchronised from the global modern governor users table.
This setting should be disabled on standalone instances, such as http://www.governorslearningpartnership.com.';
$string['searchbyschool'] = 'Search';
$string['searchbyschool_help'] = 'Enter the school name or part of it. Separate search terms with spaces.';
$string['searchbyuser'] = 'Search Email';
$string['searchbyuser_help'] = 'Enter the email of a user or part of it.';
$string['selectlea'] = ' -- Select LEA -- ';
$string['leaselect'] = 'LEA';
$string['leaselect_help'] = 'Use the dropdown to select an LEA to search within.';
$string['newleaselect'] = 'LEA';
$string['newleaselect_help'] = 'Use the dropdown to select an LEA for the new school.';
$string['newname'] = 'New school name';
$string['newname_help'] = 'Specify a name for the newly combined school.';
$string['statusselect'] = 'Status';
$string['statusselect_help'] = 'Use the dropdown to filter on the status of the shool (LIVE or DEACTIVATED)';
$string['userstatusselect'] = 'Status';
$string['userstatusselect_help'] = 'Select to show only confirmed or unconfirmed users.';
$string['usercreated'] = 'User Created';
$string['instancename'] = 'Instance Name';
$string['confirmed'] = 'Confirmed';
$string['instancename_help'] = 'The name of this instance as it appears in the moodleadmin instance table (e.g. mg_learning, mg_gladys).';
$string['exportoptions'] = 'Export Options';
$string['downloadreport'] = 'Download {$a} report.';
$string['useraddedsuccessfully'] = 'User account was created successfully!';
$string['enabledemousers'] = 'Enable Demo Users';
$string['enabledemousers_help'] = 'Enable Demo Users - this only works for the main "learning.moderngovernor.com" DLE';

// error strings
$string['error:authdbfailed'] = 'External Database authentication does not appear to be enabled. Click <a href="{$a}">here</a> to check settings.';
$string['error:authdbinvalidhost'] = 'External Database is not hosted on the same server as the instance database - school administration cannot continue.';
$string['error:noinstancename'] = 'Instance name not set - cannot sync users with global table!';
$string['error:unknownreport'] = 'Unknown report type!';
$string['error:duplicateaccount'] = 'Account already exists for user with email {$a->email} and name {$a->firstname} {$a->lastname} in {$a->lea} / {$a->school}.';
$string['error:noemailspecified'] = 'You must specify a valid email address!';
$string['error:demoleanotfound'] = 'Could not locate Demo LEA in table! Call a programmer, stat!';

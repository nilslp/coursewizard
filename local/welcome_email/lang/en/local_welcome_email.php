<?php

$string['pluginname'] = 'Welcome Emails';
$string['settings'] = 'Settings';
$string['settingsupdated'] = 'Settings Updated Successfully!';
$string['menuitem'] = 'Welcome Emails';
$string['welcome_email:config'] = 'Welcome Email Configuration';
$string['customemailsettings'] = 'Welcome Email Configuration';
$string['selfregsettings'] = 'Self Registration Configuration';
$string['viewsent'] = 'View Sent';
$string['status'] = 'Welcome Email Status';
$string['timesent'] = 'Time Sent';
$string['status:sent'] = 'Sent';
$string['status:notsent'] = 'Not Sent';
$string['status:archived'] = 'Archived';
$string['noresults'] = 'No matches';
$string['loading'] = 'Loading ...';
$string['xrecords'] = '{a} User(s) Found';
$string['searchbynameoremail'] = 'Search Users';
$string['searchbynameoremail_help'] = 'Search for users by entering part of their name or email into the input field.';
$string['emailstop'] = 'Email Enabled';
$string['timecreated'] = 'User Created';
$string['emailstopped'] = 'Disabled';
$string['hasloggedin'] = 'Has Logged In';
$string['emailnotstopped'] = 'Enabled';
$string['statusselect'] = 'Sent Status';
$string['statusselect_help'] = 'Use the dropdown to filter results where welcome emails have or have not already been sent.';
$string['resetstatus'] = 'Reset Sent Status';
$string['resetstatus_help'] = '<p>You can use the controls below to manually set the sent status for users added between the times selected.</p> 
<p>The purpose of this control is to allow users to be bulk uploaded and to have their welcome emails deferred until a time chosen by the site administrator.</p>
<p><strong>Warning:</strong>Be careful when using this feature, as it could result in users receiving their welcome email more than once.</p>';
$string['timestart'] = 'Start Time';
$string['timestart_help'] = 'Users created after the selected time will have their status updated.';
$string['timeend'] = 'End Time';
$string['timeend_help'] = 'Users created before the selected time will have their status updated.';
$string['statussetselect'] = 'Set Status To';
$string['statussetselect_help'] = 'Use the dropdown to manually set the status of users added between the times selected.';
$string['withxselecteduserssetstatus'] = 'With {$a} selected users set sent status to:';

// default strings
$string['default:customtemplatesubject'] = 'Your DLE Login Details';
$string['default:customtemplatebody'] = "Hi <firstname>,\n\n    
You've been added to the <sitename>  Dynamic Learning Environment (DLE) to access your elearning.\n
To access the DLE go to <siteurl> and login with your username and password.\n\n
Your login details: \n
Username: <username>\n
Password: welcome (you will be asked to change this the first time you login).\n\n
If you have any problems contact support@learningpool.com, do not reply to this message.\n\n
Regards,\n\n
<sitename> DLE Administrator.\n";

// Settings
$string['enablewelcomeemail'] = 'Enable Welcome Emails';
$string['custometemplatesettings'] = 'Custom Template Settings';
$string['enablewelcomeemail_help'] = '<p>If checked, the system will send new users a welcome email. You can customise the email text using the settings on this page, or leave it at the default.</p>
<p>Emails will be sent to new users on a daily basis according to the time set below.</p>';
$string['usecustomtemplate'] = 'Use Custom Template';
$string['usecustomtemplate_help'] = 'If checked, emails will be based on the template you create on this page.';
$string['customtemplatecc'] = 'CC';
$string['customtemplatecc_help'] = 'CC recipient for all welcome emails.';
$string['customtemplatesubject'] = 'Subject';
$string['customtemplatesubject_help'] = 'Set a custom subject line for your welcome emails.';
$string['customtemplatebody'] = 'Body';
$string['customtemplatebody_help'] = '<p>Add your custom email text here.</p>
<p>You can insert the following user-specific fields that will be auto-populated with the recipient\'s details:</p>
<ul>
<li>&lt;username&gt; - You can place this field anywhere in the body of the email and it will display the username of the person being sent the email.</li>
<li>&lt;firstname&gt; - This will display the first name of the person being sent the email.</li>
<li>&lt;lastname&gt; - This will display the surname of the person being sent the email.</li>
<li>&lt;sitename&gt; - This will display the name of the DLE.</li>
<li>&lt;siteurl&gt; - This will display URL of the DLE.</li>
</ul>';
$string['customtemplatefrom'] = 'From';
$string['customtemplatefrom_help'] = 'Please enter a custom from address. If left blank, the DLE administrator\'s address will be used.';
$string['welcomeemailhour'] = 'Hour';
$string['welcomeemailminute'] = 'Minute';
$string['welcomeemailtime'] = 'Send Emails Daily At: ';
$string['welcomeemailtime_help'] = 'If welcome emails are enabled, they will be sent daily at the specified time.';
$string['selfregdesc'] = '<h3>Description</h3><p>This is the email that will be sent to users who use the self subscription functionality.</p>
    <p>Click on the help icon for more information on adding dynamic data.</p>';
$string['selfregbody'] = 'Body';
$string['selfregbody_help'] = '<p>Add your custom email text here.</p>
<p>You can insert the following user-specific fields that will be auto-populated with the recipient\'s details:</p>
<ul>
<li>{$a->sitename} - This will display the name of the DLE.</li>
<li>{$a->firstname} - This will display the first name of the person being sent the email.</li>
<li>{$a->link} - This will display a clickable confirmation link.</li>
<li>{$a->admin} - This will display the support details for the DLE.</li>
</ul>';
$string['selfregcc'] = 'CC Addresses';
$string['selfregcc_help'] = '<p>Add a comma separated list of email addresses to receive duplicates of the emails sent to users that use the self registration functionality.</p>';


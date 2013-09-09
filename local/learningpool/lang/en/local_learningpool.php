<?php
$string['pluginname'] = 'Learning Pool - Common Libraries';
$string['learningpooladmin'] = 'Learning Pool';
$string['currentlyselected'] = 'Current selection';
$string['chooseorganisation'] = 'Choose organisation';
$string['browse'] = 'Browse';
$string['search'] = 'Search';
$string['error:dialognotreeitems'] = 'No items to display.';
$string['noresultsfor'] = 'No results for {$a->query}.';
$string['fullnamecontains'] = 'Full name contains';
$string['workritecategory'] = 'Workrite Settings';
$string['coSsoId'] = 'SSO ID';
$string['coSsoId_help'] = 'The Company SSO ID will be a GUID';
$string['coWsId'] = 'Web Service ID';
$string['coWsId_help'] = 'The Company web service ID will be a GUID';
$string['coLogin'] = 'Company Login';
$string['coLogin_help'] = 'This is the company login (username)';
$string['coPassword'] = 'Company Password';
$string['coPassword_help'] = 'Enter the company password';
$string['role'] = 'Role';
$string['role_help'] = 'Role must be one of "student" or "client manager".';
$string['places'] = 'Place';
$string['places_help'] = 'The place to which users are assigned. This must take the form "Group name,Member type"; for example "Accounts,member" ';
$string['wsdlurl'] = 'WSDL URL';
$string['wsdlurl_help'] = 'The web services URL - points to a WSDL file';
$string['linktext'] = 'Link text';
$string['linktext_help'] = 'The text displayed in the link that the user clicks';
$string['error:morethanxitemsatthislevel'] = 'There are too many items to display.';
$string['error:unknownreport'] = 'This report type is unrecognized!';
$string['trysearchinginstead'] = 'Try searching instead.';
$string['learningpooldleconfiguration'] = 'Configure DLE';
$string['settingsupdated'] = 'Settings were updated!';
$string['settingsnotupdated'] = 'Settings were not updated!';
$string['pausemessage'] = 'Please wait a moment while your progress is updated ... ';

// forms
$string['forcecalendarlogin'] = 'Force login for Calendar';
$string['forcecalendarlogin_help'] = 'For sites with public viewable calendar items, turning this setting on will force unauthenticated users to login when they attempt to view details on individual items';

// audit reports
$string['audit']                        = 'DLE Auditing';
$string['downloadcompletionreport']     = 'Download Completion Report';
$string['heading:courseid']             = 'Course ID';
$string['heading:coursevisible']        = 'Course Visible';
$string['heading:coursecategory']       = 'Course Category';
$string['heading:coursename']           = 'Course Fullname';
$string['heading:courselink']           = 'Course Link';
$string['heading:courseshortname']      = 'Course Shortname';
$string['heading:moduletype']           = 'Module Type';
$string['heading:modulename']           = 'Module Name';
$string['heading:modulevisible']        = 'Module Visible';
$string['heading:modulecompletion']     = 'Module Completion';
$string['heading:aggrmethod']           = 'Completion Aggregation Method';
$string['heading:gradepass']            = 'Grade to Pass';
$string['heading:gradelink']            = 'Grade Link';

// postcode lookup
$string['findaddress']                  = 'Find Address';
$string['chooseanaddress']              = 'Choose an address';
$string['error:generic']                = 'An error has occurred';
$string['error:mustprovidepostcode']    = 'You must provide a valid postcode!';
$string['error:postcodelookupauthdetailsincomplete'] = 'Authentication details for the lookup service are incomplete!
    Please contact the Site Administrator.';
$string['usepostcodelookup'] = 'Use Postcode finder on signup form';
$string['usepostcodelookup_help'] = '<p>If enabled, users can enter their postcode on the signup form to have their address auto completed.</p>
<p>In order for this service to work, you need to add valid authentication details below</p>
<p>You will also need to add user profile fields to hold details from the address request.</p>';
$string['postcodelookupserviceurl'] = 'Postcode Finder Service URL';
$string['postcodelookupserviceurl_help'] = 'Add the URL to the finder service here.';
$string['postcodelookupaccount'] = 'Account';
$string['postcodelookupaccount_help'] = 'The account name used to signup to the postcode finder service';
$string['postcodelookuppassword'] = 'Password';
$string['postcodelookuppassword_help'] = 'The password used to authenticate to the postcode finder service';
$string['postcodelookupfield_address1'] = 'Address 1 Field';
$string['postcodelookupfield_address1_help'] = 'The shortname of the user profile field mapped to Address 1';
$string['postcodelookupfield_address2'] = 'Address 2 Field';
$string['postcodelookupfield_address2_help'] = 'The shortname of the user profile field mapped to Address 2';
$string['postcodelookupfield_address3'] = 'Address 3 Field';
$string['postcodelookupfield_address3_help'] = 'The shortname of the user profile field mapped to Address 3';
$string['postcodelookupfield_address4'] = 'Address 4 Field';
$string['postcodelookupfield_address4_help'] = 'The shortname of the user profile field mapped to Address 4';
$string['postcodelookupfield_town'] = 'Town/City Field';
$string['postcodelookupfield_town_help'] = 'The shortname of the user profile field mapped to Town/City';
$string['postcodelookupfield_country'] = 'Country Field';
$string['postcodelookupfield_country_help'] = 'The shortname of the user profile field mapped to Country';
$string['postcodelookupfield_postcode'] = 'Postcode Field';
$string['postcodelookupfield_postcode_help'] = 'The shortname of the user profile field mapped to Postcode';
$string['postcodelookupconfiguration'] = 'Postcode Lookup';
$string['postcodeserviceaccountdetails'] = 'Account Details';
$string['postcodeservicefieldmappings'] = 'Field Mapping';

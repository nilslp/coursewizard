<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service template plugin related strings
 * @package   local
 * @subpackage lp_webservices
 * @copyright 2013 Learning Pool
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Learning Pool Webservices';
$string['configurewebservices'] = 'Configure Webservices';
$string['setup'] = 'Setup';
$string['tokensetup'] = 'Setup General Users';
$string['metacompliancesetup'] = 'Setup Meta-Compliance User';

$string['configurewebservices'] = 'Configure Webservices';
$string['setup'] = 'Setup';
$string['generatetokens'] = 'Generate Authentication Tokens';
$string['generatetokens_help'] = '<em>Clicking this button will generate authentication tokens for all users on a site to allow them access to the Learning Pool webservices functions.</em>
<em>Note that this is a one time only action - any new users will have auth tokens generated automatically when they are created.</em>';
$string['generatetokenscomplete'] = '<em>Auth tokens have already been generated.</em>';
$string['generatetokenssuccess'] = 'Successfully generated tokens!';

$string['metacomplianceusername'] = 'Meta Compliance Username';
$string['metacomplianceusername_help'] = 'Username for a new meta-compliance user account used to access webservices';
$string['metacompliancepassword'] = 'Meta Compliance Password';
$string['metacompliancepassword_help'] = 'Password to authenticate the new meta-compliance user';
$string['createmetacomplianceuser'] = 'Create Meta-compliance user';
$string['createmetacomplianceuser_help'] = '<em>Clicking this button will create a new role and a new user that can access the meta-compliance webservice to check course completion for a given user.
Once created, you do not need to recreate this user and role.</em>';
$string['createmetacomplianceusercomplete'] = '<em>User exists with username {$a->username} and token {$a->token}.</em>';
$string['generatemetacomplianceusersuccess'] = 'Successfully generated user/role!';
$string['metacomplianceroledesc'] = 'Limited role access to webservices for meta-compliance';

$string['error:tokensalreadygenerated'] = 'Tokens have already been generated for this site!';
$string['error:metacompliancerolealreadycreated'] = 'That role has already been created!';
$string['error:invaliduserdetails'] = 'The user details provided must not be empty';
$string['error:usernameexists'] = 'That username already exists!';
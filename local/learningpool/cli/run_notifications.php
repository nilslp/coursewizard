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
 * This script runs plugin upgrades in cli mode - cannibalised from
 * admin/cli/upgrade.php
 * 
 * @package    local
 * @subpackage learningpool
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');       // various admin-only functions
require_once($CFG->libdir.'/upgradelib.php');     // general upgrade/install related functions
require_once($CFG->libdir.'/clilib.php');         // cli only functions
require_once($CFG->libdir.'/environmentlib.php');
require_once($CFG->libdir.'/pluginlib.php');

if (empty($CFG->version)) {
    cli_error(get_string('missingconfigversion', 'debug'));
}

require("$CFG->dirroot/version.php");       // defines $version, $release and $maturity

// Test plugin dependencies.
if (!plugin_manager::instance()->all_plugins_ok($version)) {
    cli_error(get_string('pluginschecktodo', 'admin'));
}

// unconditionally upgrade
upgrade_noncore(true);

// log in as admin - we need doanything permission when applying defaults
$admins = get_admins();
$admin = reset($admins);
session_set_user($admin);

// apply all default settings, just in case do it twice to fill all defaults
admin_apply_default_settings(NULL, false);
admin_apply_default_settings(NULL, false);

echo get_string('cliupgradefinished', 'admin')."\n";
exit(0); // 0 means success

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
 * This plugin can help upgrade site with a large number of question attempts
 * from Moodle 2.0 to 2.1.
 *
 * This screen is the main entry-point to the plugin, it gives the admin a list
 * of options available to them.
 *
 * @package    local
 * @subpackage course access manager
 * @copyright  2011 Learning Pool (Rachael Harkin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /** INCLUDES
  * **/
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/learningpool/js/setup.php');

global $USER, $PAGE;
/** LOGON AND ROLE CAPABILITY **/
require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

/** Setting up page url for access to the plugin view**/
admin_externalpage_setup('lpscheduler');

// rendering the page
$PAGE->requires->css('/local/dlelegacytools/style.css');
include_jquery();
$PAGE->requires->js('/local/dlelegacytools/javascript/lpscheduler_behaviours.js');
$renderer = $PAGE->get_renderer('local_dlelegacytools');
echo $renderer->index_page();


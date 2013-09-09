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
 * This screen is the main entry-point to the plugin, it gives the admin a list 
 * of options available to them.
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

$PAGE->set_url(new moodle_url('/local/lp_courseaccessmanager/index.php'));
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('accessdenied','local_lp_courseaccessmanager'));
$PAGE->set_heading(get_string('accessdenied','local_lp_courseaccessmanager'));
$renderer = $PAGE->get_renderer('local_lp_courseaccessmanager');

echo $renderer->access_denied();

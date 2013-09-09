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

$context = get_context_instance(CONTEXT_SYSTEM);

require_login();
require_capability('local/lp_courseaccessmanager:manageaccess', $context);

$PAGE->set_url(new moodle_url('/local/lp_courseaccessmanager/index.php'));
$PAGE->set_context($context);

#admin_externalpage_setup('lp_courseaccessmanager','',null,'',array('pagelayout'=>'base')); // base layout - full width (ish)
admin_externalpage_setup('lp_courseaccessmanager');    // admin layout - with blocks

$renderer = $PAGE->get_renderer('local_lp_courseaccessmanager');

$jsconfig = array(
	'name' => 'local_lp_courseaccessmanager',
	'fullpath' => '/local/lp_courseaccessmanager/lpcourseaccessmanager.js',
	'requires' => array(
                        'node',
                        'base', 
                        'json',
                        'io',
                        'event',
                        'node-event-simulate',
                        'yui2-treeview',
                        'yui2-container',
                        'yui2-dragdrop'
                    )
            );

$PAGE->requires->js_init_call('M.local_lp_courseaccessmanager.init', null, false, $jsconfig);
#$PAGE->requires->css('/local/lp_courseaccessmanager/style.css');

echo $renderer->index_page();

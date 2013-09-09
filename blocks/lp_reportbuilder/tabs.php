<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * 
 * This program is free software; you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation; either version 2 of the License, or     
 * (at your option) any later version.                                   
 *                                                                       
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */

/**
 * Display tabs on report settings pages
 *
 * Included in each settings page
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// assumes the report id variable has been set in the page
if (!isset($currenttab)) {
    $currenttab = 'general';
}

$tabs = array();
$row = array();
$activated = array();
$inactive = array();

$row[] = new tabobject('general',$CFG->wwwroot.'/blocks/lp_reportbuilder/general.php?id='.$id, get_string('general'));
$row[] = new tabobject('columns',$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?id='.$id, get_string('columns','block_lp_reportbuilder'));
$row[] = new tabobject('filters',$CFG->wwwroot.'/blocks/lp_reportbuilder/filters.php?id='.$id, get_string('filters','block_lp_reportbuilder'));
$row[] = new tabobject('content',$CFG->wwwroot.'/blocks/lp_reportbuilder/content.php?id='.$id, get_string('content','block_lp_reportbuilder'));
// hide access tab for embedded reports
// Removing this to allow stricter control on who can view the embedded reports, i.e. facetoface specifically
//if(!$report->embeddedurl) {
    $row[] = new tabobject('access',$CFG->wwwroot.'/blocks/lp_reportbuilder/access.php?id='.$id, get_string('access','block_lp_reportbuilder'));
//}

$tabs[] = $row;
$activated[] = $currenttab;

// print out tabs
print_tabs($tabs, $currenttab, $inactive, $activated);

<?php
/**
 *  this script automates the adding of a course_list type block to the front page
 *  UPDATE: this script now responsible for much more, but keeping the filename
 *  so Seumas doesn't need to update his script!
 */

define('CLI_SCRIPT',true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG,$DB;

// we need to macgyver up a page and set its context before using the block manger
$page = new moodle_page();
$page->set_pagetype('site-index');
$courseid = $DB->get_field('course','id',array('category'=>0));
$context = get_context_instance(CONTEXT_COURSE,$courseid);
$page->set_context($context);

// create a block manager
$bmanager = new block_manager($page);
$bmanager->add_region('side-pre');
$bmanager->add_region('side-post');

$bmanager->set_default_region('side-post');
// check for existing block instance before adding one ...
$exists = $DB->get_field('block_instances','id',array('blockname'=>'course_list','parentcontextid'=>$context->id));
if (!$exists) {
    // no block, fire away
    $bmanager->add_block_at_end_of_default_region('course_list');
    echo "Successfully created course list block!\n";
} else {
    // too many blocks, you fool
    echo "A course list block already exists!\n";
}

// check for existing block instance before adding one ...
$exists = $DB->get_field('block_instances','id',array('blockname'=>'lp_reportbuilder','parentcontextid'=>$context->id));
if (!$exists) {
    // no block, fire away
    $bmanager->add_block_at_end_of_default_region('lp_reportbuilder');
    echo "Successfully created report builder block!\n";
} else {
    // too many blocks, you fool
    echo "A report builder block already exists!\n";
}

// check for existing block instance before adding one ...
$exists = $DB->get_field('block_instances','id',array('blockname'=>'lp_charts','parentcontextid'=>$context->id));
if (!$exists) {
    // no block, fire away
    $bmanager->add_block_at_end_of_default_region('lp_charts');
    echo "Successfully created charts block!\n";
} else {
    // too many blocks, you fool
    echo "A charts block already exists!\n";
}

// check for existing block instance before adding one ...
$exists = $DB->get_field('block_instances','id',array('blockname'=>'lp_course_progress','parentcontextid'=>$context->id));
if (!$exists) {
    // no block, fire away
    $bmanager->add_block_at_end_of_default_region('lp_course_progress');
    echo "Successfully created course progress block!\n";
} else {
    // too many blocks, you fool
    echo "A course progress block already exists!\n";
}

// update permissions ...
//  - remove permissions for charts and report builder for non report/site admins
$DB->execute(
        "DELETE FROM {role_capabilities}
            WHERE roleid NOT IN (
                SELECT id FROM {role}
                WHERE shortname IN (?,?)
            )
            AND capability IN (?,?)",
        array('site_admin','report_admin','block/lp_charts:view','block/lp_reportbuilder:viewreports')
        );

// - allow site admins the following permissions
$syscontext = get_system_context();
$role_cap = new stdClass();
$role_cap->contextid = $syscontext->id;
$role_cap->roleid = $DB->get_field('role','id',array('shortname'=>'site_admin'));
$role_cap->permission = 1;
$role_cap->timemodified = time();
$role_cap->modifierid = 0;

echo "Configuring capabilities ...\n";

// edit blocks
$role_cap->capability = 'moodle/block:edit';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to add capability {$role_cap->capability} for site admin! (Perhaps it already exists?)\n";
}

// view blocks
$role_cap->capability = 'moodle/block:view';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to add capability {$role_cap->capability} for site admin! (Perhaps it already exists?)\n";
}

// manage blocks
$role_cap->capability = 'moodle/site:manageblocks';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to add capability {$role_cap->capability} for site admin! (Perhaps it already exists?)\n";

    
}

// manage hierarchy
$role_cap->capability = 'block/lp_hierarchy:manage';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to add capability {$role_cap->capability} for site admin! (Perhaps it already exists?)\n";
}

// view courses
$role_cap->capability = 'moodle/course:view';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to add capability {$role_cap->capability} for site admin! (Perhaps it already exists?)\n";
}

// remove access to the navigation block for guests!
$role_cap->roleid = $DB->get_field('role','id',array('shortname'=>'guest'));
$role_cap->permission = -1;
$instanceid = $DB->get_field('block_instances','id',array('blockname'=>'navigation'));
$blockcontext = get_context_instance(CONTEXT_BLOCK, $instanceid);
$role_cap->contextid = $blockcontext->id;
$role_cap->capability = 'moodle/block:view';
$exists = $DB->get_field(
        'role_capabilities',
        'id',
        array('capability'=>$role_cap->capability,'contextid'=>$role_cap->contextid,'roleid'=>$role_cap->roleid)
        );
if (!$exists && !$DB->insert_record('role_capabilities',$role_cap)){
    echo "Failed to configure capability {$role_cap->capability} for guest! (Perhaps it already exists?)\n";
}

// remove permission for students to unenrol themselves from courses
$role_cap->roleid = $DB->get_field('role','id',array('shortname'=>'student'));
$DB->delete_records('role_capabilities',array('roleid'=>$role_cap->roleid, 'capability'=>'enrol/self:unenrolself'));

// allow site_admins to assign all roles sitewide ...
$rec = new stdClass();
$rec->roleid = $DB->get_field('role','id',array('shortname'=>'site_admin'));
$roleids = $DB->get_records('role',null,'id ASC','id');
foreach ($roleids as $r){
    $rec->allowassign=$r->id;
    $exists = $DB->get_field('role_allow_assign','id',array('roleid'=>$rec->roleid,'allowassign'=>$rec->allowassign));
    if (!$exists && !$DB->insert_record('role_allow_assign',$rec)){
        echo "Failed to allow role assignment for role {$rec->allowassign}!\n";
    }
}
unset($rec);

// set up default hidden fields
$defaultfields = array('city','country');
$config = $DB->get_record('config',array('name'=>'hiddenuserfields'));
$hiddenfields = explode(',',$config->value);
$hiddenfields = array_unique(array_merge($defaultfields,$hiddenfields));
$config->value = implode(',',$hiddenfields);
$DB->update_record('config',$config);
$DB->execute("UPDATE {config} SET value=? WHERE name=?",array('GB','country'));
$DB->execute("UPDATE {config} SET value=? WHERE name=?",array('London','defaultcity'));
$DB->execute("UPDATE {user} SET city=? WHERE city IS NULL OR city=?",array('London',''));
$DB->execute("UPDATE {user} SET country=? WHERE country IS NULL OR country=?",array('GB',''));

// finally, hide the hierarchy block from the selection list
if (!$DB->execute("UPDATE {block} SET visible=0 WHERE name='lp_hierarchy'")){
    echo "Failed to hide hierarchy block!\n";
}

echo "Completed!\n";







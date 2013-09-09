<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $DB;
require_once( $CFG->dirroot.'/local/learningpool/utils.php' );
require_once( 'lib.php' );

$type      = optional_param('type', '', PARAM_ALPHA); 

$contextsystem = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($contextsystem);

require_login();
require_capability('moodle/site:config', $contextsystem);

$PAGE->set_url('/local/moderngovernor/report.php', array('type' => $type));

switch ($type) {
    case 'school':
        $enabled = get_string('enabled', 'local_moderngovernor');
        $disabled = get_string('disabled', 'local_moderngovernor');
        $depthid = $DB->get_field('lp_hierarchy_depth', 'id', array('level' => 2));
        $fields = array(
            array('heading' => get_string('leaname', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'lea'),
            array('heading' => get_string('schoolname', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'school'),
            array('heading' => get_string('status', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'live')
        );
        
        $select = "SELECT level2.id, level1.fullname AS lea, level2.fullname AS school, IF(level2.visible,'{$enabled}','{$disabled}') AS live ";
        $query = " FROM {lp_hierarchy} level2 JOIN {lp_hierarchy} level1 ON level1.id=level2.parentid WHERE level2.depthid={$depthid}";
        $count = $DB->count_records_sql("SELECT COUNT('x') {$query}");
        break;
    case 'user':
        $enabled = get_string('enabled', 'local_moderngovernor');
        $disabled = get_string('disabled', 'local_moderngovernor');
        $fields = array(
            array('heading' => get_string('firstname'), 'format' => 'string', 'fieldname' => 'firstname'),
            array('heading' => get_string('lastname'), 'format' => 'string', 'fieldname' => 'lastname'),
            array('heading' => get_string('username'), 'format' => 'string', 'fieldname' => 'username'),
            array('heading' => get_string('email'), 'format' => 'string', 'fieldname' => 'email'),
            array('heading' => get_string('usercreated', 'local_moderngovernor'), 'format' => 'datetime', 'fieldname' => 'timecreated'),
            array('heading' => get_string('leaname', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'lea'),
            array('heading' => get_string('schoolname', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'school'),
            array('heading' => get_string('deleted'), 'format' => 'string', 'fieldname' => 'deleted'),
            array('heading' => get_string('confirmed', 'local_moderngovernor'), 'format' => 'string', 'fieldname' => 'confirmed')
        );
        
        if ((int)get_config('local_moderngovernor','synchierarchy')) {
            $mg_cfg = get_moderngovernor_config();
            $lealist = get_moderngovernor_lea_insql();
            $select = "SELECT CONCAT(gu.id,'-',u.id) AS id, u.firstname, u.lastname, u.username, u.email, gu.date_added AS timecreated, level1.fullname AS lea, level2.fullname AS school, IF(LOCATE('.DELETED', gu.username) > 0, 1, 0) AS deleted, gu.confirmed ";
            $query = " FROM  `{$mg_cfg->db}`.`{$mg_cfg->table}` gu LEFT JOIN {user} u ON (gu.email=u.email AND gu.lea_id IN ($lealist)) JOIN {lp_user_hierarchy} uh ON uh.userid=u.id JOIN {lp_hierarchy} level2 ON level2.id=uh.hierarchyid JOIN {lp_hierarchy} level1 ON level1.id=level2.parentid WHERE gu.lea_id IN ($lealist)";
        } else {            
            $select = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.timecreated, level1.fullname AS lea, level2.fullname AS school, u.deleted, u.confirmed ";
            $query = " FROM {user} u JOIN {lp_user_hierarchy} uh ON uh.userid=u.id JOIN {lp_hierarchy} level2 ON level2.id=uh.hierarchyid JOIN {lp_hierarchy} level1 ON level1.id=level2.parentid ";
        }
        $count = $DB->count_records_sql("SELECT COUNT('x') {$query}");
        break;
    default:
        $title = get_string('error');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->set_pagelayout('standard');
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('error:unknownreport', 'local_moderngovernor'), 'notifyfailure');
        echo $OUTPUT->footer();
        exit;
}

local_learningpool_download_xls("{$type}_report", $fields, $select.$query, $count);
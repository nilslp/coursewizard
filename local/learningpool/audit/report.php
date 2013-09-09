<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $OUTPUT, $DB;
require_once( dirname(dirname(__FILE__)).'/utils.php' );
require_once( dirname(__FILE__).'/lib.php' );

$type      = optional_param('type', '', PARAM_ALPHA); 

$contextsystem = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($contextsystem);

require_login();
require_capability('moodle/site:config', $contextsystem);

$PAGE->set_url('/local/learningpool/audit/report.php', array('type' => $type));

switch ($type) {
    case 'completion':        
        $fields = array(
            array('heading' => get_string('heading:courseid', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'id'),
            array('heading' => get_string('heading:coursevisible', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'coursevisible'),
            array('heading' => get_string('heading:coursecategory', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'coursecategory'),
            array('heading' => get_string('heading:coursename', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'fullname'),
            array('heading' => get_string('heading:courselink', 'local_learningpool'), 'format' => 'url', 'fieldname' => 'courselink', 'filter' => new append_filter("{$CFG->wwwroot}/course/completion.php?id=") ),
            array('heading' => get_string('heading:courseshortname', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'shortname'),
            array('heading' => get_string('heading:moduletype', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'type'),
            array('heading' => get_string('heading:modulename', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'name'),
            array('heading' => get_string('heading:modulevisible', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'modulevisible'),
            array('heading' => get_string('heading:modulecompletion', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'modulecompletion'),
            array('heading' => get_string('heading:aggrmethod', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'aggrmethod'),
            array('heading' => get_string('heading:gradepass', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'gradepass'),
            array('heading' => get_string('heading:gradelink', 'local_learningpool'), 'format' => 'string', 'fieldname' => 'gradelink', 'filter' => new append_filter("{$CFG->wwwroot}/grade/edit/tree/item.php?") )
        );        
        
        $query = "SELECT cm.id AS uniqid, c.id, c.visible AS coursevisible, cc.name AS coursecategory,
                  c.fullname, c.id AS courselink, c.shortname, 'scorm' AS type,  s.name,
                  cm.visible AS modulevisible, ccs.module AS modulecompletion, IF( am.method = 1 , 'All', IF(am.method = 2, 'ANY', 'NOT SET')) AS aggrmethod, g.gradepass,
                  CONCAT('courseid=',c.id,'&id=',g.id) AS gradelink
                  FROM {course} c
                  JOIN {scorm} s ON c.id = s.course
                  JOIN {course_categories} cc ON cc.id = c.category
                  JOIN {course_modules} cm ON cm.course = c.id AND cm.instance = s.id
                  JOIN {context} cx ON cm.id = cx.instanceid AND cx.contextlevel = 70
                  JOIN {grade_items} g ON g.courseid = c.id AND g.iteminstance = s.id AND g.itemmodule='scorm'
                  LEFT JOIN {course_completion_criteria} ccs ON ccs.course = c.id AND ccs.moduleinstance = cm.id
                  LEFT JOIN {course_completion_aggr_methd} am ON am.course = c.id AND am.criteriatype IS NULL
                  UNION
                  SELECT cm.id AS uniqid, c.id, c.visible AS coursevisible, cc.name AS coursecategory,
                  c.fullname, c.id AS courselink, c.shortname,  'quiz' AS type, q.name,
                  cm.visible AS modulevisible, ccs.module AS modulecompletion, IF( am.method = 1 , 'All', IF(am.method = 2, 'ANY', 'NOT SET')) AS aggrmethod, g.gradepass,
                  CONCAT('courseid=',c.id,'&id=',g.id) AS gradelink
                  FROM {course} c
                  JOIN {quiz} q ON q.course = c.id
                  JOIN {course_categories} cc ON cc.id = c.category
                  JOIN {course_modules} cm ON cm.course = c.id AND cm.instance = q.id AND cm.course = c.id
                  JOIN {context} cx ON cm.id = cx.instanceid AND cx.contextlevel = 70
                  JOIN {grade_items} g ON g.courseid = c.id AND g.iteminstance = q.id AND g.itemmodule='quiz'
                  LEFT JOIN {course_completion_criteria} ccs ON ccs.course = c.id AND ccs.moduleinstance = cm.id
                  LEFT JOIN {course_completion_aggr_methd} am ON am.course = c.id AND am.criteriatype IS NULL";
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM ({$query}) AS total");
        break;
    default:
        $title = get_string('error');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->set_pagelayout('standard');
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('error:unknownreport', 'local_learningpool'), 'notifyfailure');
        echo $OUTPUT->footer();
        exit;
}

local_learningpool_download_xls("{$type}_report", $fields, $query, $count);
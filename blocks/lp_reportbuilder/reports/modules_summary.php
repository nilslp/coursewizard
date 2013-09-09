<?php
global $CFG, $DB;

require_once('../../../config.php');
require_once("base_report.php");


$courseid = optional_param('courseid', 0, PARAM_INT);
$course_name = get_string('allcourses','block_lp_reportbuilder');

if ($courseid != 0) {    
    $course_name = $DB->get_field('course', 'fullname', array('id' => $courseid));
}

$report = new base_report_embedded('modules_summary', '/blocks/lp_reportbuilder/reports/modules_summary.php', $course_name);

$report->run();
?>

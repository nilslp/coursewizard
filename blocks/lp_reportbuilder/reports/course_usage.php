<?php
require_once("base_report.php");

$report = new base_report_embedded('course_usage', '/blocks/lp_reportbuilder/reports/course_usage.php');

$report->run();
?>

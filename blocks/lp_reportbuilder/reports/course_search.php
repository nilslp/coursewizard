<?php
require_once("base_report.php");

$report = new base_report_embedded('course_search', '/blocks/lp_reportbuilder/reports/course_search.php');

$report->run();
?>
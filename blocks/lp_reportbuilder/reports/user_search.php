<?php
require_once("base_report.php");

$report = new base_report_embedded('user_search', '/blocks/lp_reportbuilder/reports/user_search.php');

$report->run();
?>

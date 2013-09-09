<?php
require_once("base_report.php");

$report = new base_report_embedded('user_export', '/blocks/lp_reportbuilder/reports/user_export.php');

$report->run();
?>

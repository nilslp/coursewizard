<?php
require_once("base_report.php");

$report = new base_report_embedded('trent_export', '/blocks/lp_reportbuilder/reports/trent_export.php');

$report->run();
?>

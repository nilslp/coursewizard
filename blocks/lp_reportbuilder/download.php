<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
global $CFG;
require_login();

$f = required_param('f', PARAM_ALPHANUM); // temp filename
$format = required_param('format', PARAM_ALPHA); // file format
$id = required_param('id', PARAM_INT); // report identifier

$report = new reportbuilder($id, null, false);

$filename = sanitize_filename($report->fullname) . '.' .$format;

$path = $CFG->dataroot.'/'.REPORT_BUILDER_DOWNLOAD_PATH.$f;
if (!empty($f) && file_exists($path)) {
    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");
    echo file_get_contents($path);
    unlink($path);
    die;
}
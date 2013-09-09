<?php
// yes browser, we are css ...
header('Content-Type: text/css; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

$toc = optional_param('toc',0,PARAM_INT);

$css = array();

// check config for hiding enterbutton
if ('on' === get_config('local/dlelegacytools', 'scormhideenterbtn') && !empty($toc)){
    $css []= 'body#page-mod-scorm-view #theform input[type="submit"] { display: none; } ';
}

// check config for custom login func
if (get_config('local/dlelegacytools','customlogin')) {
    $css[] = 'div.forgetpass{display:none;}';
}

// prep css for display
$css = implode('',$css);

echo <<<CSS
$css
CSS;
exit;


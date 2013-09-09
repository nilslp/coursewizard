<?php
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $OUTPUT;

$course = optional_param('course', 0, PARAM_INT);
$sessionkey = sesskey();

echo <<<JSCRIPT
YUI().use('node','event',function(Y){
    var list = Y.all('body#page-course-view-topics span.commands a.editing_delete');
    
    list && list.each(function(node){ 
        var url = node.getAttribute('href');
        var delete_param = url.substring(url.lastIndexOf('&delete')); 
        node.setAttribute('href', '{$CFG->wwwroot}/local/dlelegacytools/confirmdelete/confirmmoddelete.php?sesskey={$sessionkey}&sr=1' + delete_param);
    });
});
JSCRIPT;

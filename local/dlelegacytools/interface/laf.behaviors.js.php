<?php
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $OUTPUT;

$course         = optional_param('course',0,PARAM_INT);
$qcb            = optional_param('qcb',0,PARAM_INT);

$cfg = array();
$cfg['qcb'] = $qcb;
$cfg['courselink'] = $OUTPUT->continue_button(new moodle_url('/course/view.php?id='.$course));

$cfg = json_encode($cfg);

echo <<<JSCRIPT
YUI().use('node','event',function(Y){
    var cfg = $cfg;
    var el = {};
    
    if (cfg.qcb){
        el = Y.one('#region-main div.region-content');
        el && el.append( Y.Node.create(
            '<div class="buttons">'+cfg.courselink+'</div>'
        ));
    }
});
JSCRIPT;

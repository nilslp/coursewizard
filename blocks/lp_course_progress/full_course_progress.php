<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/lp_course_progress/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once($CFG->libdir . '/pagelib.php');

global $PAGE, $CFG;

require_login();

$PAGE->set_context(build_context_path()); 
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_course_progress/full_course_progress.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('blocktitle', 'block_lp_course_progress'));

$renderer = $PAGE->get_renderer('block_lp_course_progress');

$embedded_version = optional_param('embedded', 0, PARAM_BOOL);
$show_header = (!$embedded_version) ? 1 : 0;
$show_header = optional_param('header', $show_header, PARAM_BOOL);
$show_meter_links = optional_param('link', 1, PARAM_BOOL);

$show_meter = true;
/*
$show_header = true;
if ($embedded_version) {
    // Hide the header on embedded version
    $show_header = false;
}
*/

if (!$embedded_version) {
    // Render the header
    
    $jsconfig = array(
	'name' => 'block_lp_course_progress',
	'fullpath' => '/blocks/lp_course_progress/javascript/full_course_progress.js',
        'strings' => array(
            array('blocktitle', 'block_lp_course_progress')
        ),
	'requires' => array(
                        'node',
                        'node-load',
                        'event',
                        'selector-css3',
                        'io-base',
                        'json-parse',
                        'event-hover',
                        'get',
                        'anim',
                        'panel'
        )
    );

    $PAGE->requires->js_init_call('M.block_lp_course_progress.init', null, false, $jsconfig);
    
    echo $OUTPUT->header();
}

echo $renderer->render_course_progress($show_meter, $show_header, $show_meter_links);

if (!$embedded_version) {
    // Render the footer
    echo $OUTPUT->footer();    
}
?>
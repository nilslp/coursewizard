<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/lp_charts/lib.php');

global $PAGE;

require_login();

$PAGE->set_context(get_context_instance_by_id(1, IGNORE_MISSING));

$url = new moodle_url("/blocks/lp_charts/index.php");
$PAGE->set_url($url);

$PAGE->navbar->add(get_string('blocktitle', 'block_lp_charts'));

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname);
        
echo $OUTPUT->header();

echo get_charts_html();

echo $OUTPUT->footer();
<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/formslib.php');
global $USER, $OUTPUT, $CFG;
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('lp_webservices_configure');

$mform = new lp_webservices_configure_form();

echo $OUTPUT->header();

if ($mform->is_submitted()) {
    $mform->process();
}

$mform->display();

echo $OUTPUT->footer();
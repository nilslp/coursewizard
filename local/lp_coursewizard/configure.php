<?php

require_once('../../config.php');
global $CFG, $OUTPUT;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( dirname(__FILE__).'/formslib.php' );

admin_externalpage_setup( 'lpcoursewizardconfig' );

$mform = new lpcoursewizardform();
echo $OUTPUT->header();
if ($mform->is_submitted()) {
    if ($mform->process()) {
        echo $OUTPUT->notification(get_string('settingsupdated', 'local_lp_coursewizard'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('settingsnotupdated', 'local_lp_coursewizard'), 'notifyfailure');
    }
}

// show the settings form
$mform->display();

echo $OUTPUT->footer();


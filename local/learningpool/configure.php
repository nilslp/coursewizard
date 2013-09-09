<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( dirname(__FILE__).'/formslib.php' );

admin_externalpage_setup( 'learningpooldleconfiguration' );

$mform = new dleglobalsettingsform();
echo $OUTPUT->header();
if ($mform->is_submitted()) {
    if ($mform->process()) {
        echo $OUTPUT->notification(get_string('settingsupdated', 'local_learningpool'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('settingsnotupdated', 'local_learningpool'), 'notifyfailure');
    }
}

// show the settings form
$mform->display();

echo $OUTPUT->footer();


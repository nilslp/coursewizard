<?php

require_once('../../config.php');
global $CFG, $OUTPUT;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'lib.php' );
require_once( 'formslib.php' );

admin_externalpage_setup( 'moderngovernorindex' );

$data = get_config('local_moderngovernor');

// instantiate form
$mform = new moderngovernorsettingsform();
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/moderngovernor/index.php');
}

echo $OUTPUT->header();

// process form
if ($mform->process()) {
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_moderngovernor'), 'notifysuccess');
}

// display form
$mform->display();

echo $OUTPUT->footer();

<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'formslib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'selfregemailadmin' );

$data = get_config('local_welcome_email');

// instantiate form
$mform = new welcomeemailselfregsettingsform();
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/welcome_email/admin.php');
}

echo $OUTPUT->header();

// process form
if ($mform->process()) {
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_welcome_email'), 'notifysuccess');
}

// display form
$mform->display();

echo $OUTPUT->footer();

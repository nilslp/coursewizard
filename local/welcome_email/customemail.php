<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'formslib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'welcomeemailadmin' );

$data = get_config('local_welcome_email');
if (isset($data->customtemplatebody)) {
    $data->customtemplatebody = array('text' => html_entity_decode($data->customtemplatebody, ENT_COMPAT, 'UTF-8'), 'format'=> 1);                
}

if (isset($data->welcomeemailtime)) {
    $data->welcomeemailhour = (int) substr($data->welcomeemailtime, 0, 2);
    $data->welcomeemailminute = (int) substr($data->welcomeemailtime, 2, 2);    
}

// instantiate form
$mform = new welcomeemailsettingsform();
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

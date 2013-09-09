<?php

require_once('../../../config.php');
global $CFG, $OUTPUT;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'local_workwrite_admin' );

$data = get_config('soap_login')  ;

// instantiate form
$mform = new local_lp_workriteform();
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/learningpool/workrite/settings.php');
}
// process form
$mform->process();
echo $OUTPUT->header();

// display form
$mform->display();
echo $OUTPUT->footer();

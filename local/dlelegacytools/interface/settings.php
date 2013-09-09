<?php // $Id$

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__).'/lib.php');
global $CFG,$OUTPUT;

admin_externalpage_setup('dlelafsettings');

$mform = new local_dlt_lafsettingsform();
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/dlelegacytools/interface/settings.php');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname','local_dlelegacytools'));

$mform->process();
$mform->display();

echo $OUTPUT->footer();

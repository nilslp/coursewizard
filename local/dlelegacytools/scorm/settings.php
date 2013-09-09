<?php // $Id$

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__).'/lib.php');

admin_externalpage_setup('dlescormsettings');

$mform = new local_dlt_scormsettingsform();
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/local/dlelegacytools/scorm/settings.php');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname','local_dlelegacytools'));

$mform->process();
$mform->display();

echo $OUTPUT->footer();

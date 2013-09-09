<?php 
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

global $CFG;
/* @package    local
 * @subpackage lpscheduler_welcomeemail
 * @copyright  2012 Learning Pool (Rachael Harkin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this area is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__).'/lib.php');

global $PAGE,$OUTPUT,$CFG;;

$context = get_context_instance(CONTEXT_SYSTEM);

require_login();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/dlelegacytools/lpscheduler/welcomeemail/index.php'));
$PAGE->set_context($context);

// admin_externalpage_setup('welcomeemail');

$PAGE->requires->css('/local/dlelegacytools/lpscheduler/welcomeemail/style.css');

$jsconfig = array(
        'name' => 'local_dlelegacytools_lpscheduler_welcomeemail',
        'fullpath' => '/local/dlelegacytools/lpscheduler/welcomeemail/js/welcomemail.js',
        'requires' => array(
                        'node',
                        'event'
                    )
            );

$PAGE->requires->js_init_call('M.local_dlelegacytools_lpscheduler_welcomeemail.init', null, false, $jsconfig);

$email_lib=new lpscheduler_welcomeemail_lib();

echo $OUTPUT->header();

// DPMH - plugin is deprecated. See local/welcome_email instead.
$newurl = $CFG->wwwroot.'/local/welcome_email/index.php';
echo $OUTPUT->notification("Welcome Emails are no longer managed on this page, please go <a href=\"{$newurl}\">here</a> instead.", 'notifyfailure');

echo $OUTPUT->footer();

exit;

echo html_writer::start_tag('div',array('id'=>'main'));

$mform= new local_lpscheduler_welcomemail_form();

$fromform = $mform->get_data(); // capture form settings

if ($fromform) {

    if(empty($fromform->submitbutton)) {
       echo $OUTPUT->notification(get_string('lpscheduler_settings_error','local_dlelegacytools'), 'notifyfailure');
    }

    if($email_lib->welcomeemail_update_instance($fromform)) {
        echo $OUTPUT->notification(get_string('update_success','local_dlelegacytools'), 'notifysuccess');
    } 
    else {
       echo $OUTPUT->notification(get_string('update_fail','local_dlelegacytools'), 'notifyfailure');
    }
}

$mform->display(); // build the form settings page
echo html_writer::end_tag('div');
echo $OUTPUT->footer();
 ?>
<?php
require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once('../lib.php');

global $CFG,$DB,$USER;

$return = array();
$return['success'] = false;

$sesskey = required_param('sesskey', PARAM_RAW);

if (!confirm_sesskey($sesskey)) {
    $results['message'] = get_string('error:badsesskey','local_lp_coursewizard');
    echo json_encode($results);
    exit;
}

$coursename = required_param('cname', PARAM_TEXT);
$courseshortname = required_param('cshortname', PARAM_TEXT);
$coursedesc = required_param('cdesc', PARAM_TEXT);
        
$filename = get_config('local_lp_coursewizard', 'templatelocation');
$table = 'files';
$result = $DB->get_record($table, array('filename'=>$filename,'mimetype'=>'application/vnd.moodle.backup'),'*');

//grab the file object using get_area_files
$fs = get_file_storage();
$files = $fs->get_area_files($result->contextid, $result->component, $result->filearea);
/** @var stored_file */
$found = null;
foreach ($files as $file) {
    if ($file->get_id() == $result->id) {
        $found = $file;
    }
}
if (!$found) {
    $return['stage'] = 'findbackup';
    $return['message'] = 'Couldn\'t find backup file';
    echo json_encode($return);
    exit;
}

// Unzip backup
$rand = $USER->id;
while (strlen($rand) < 10) {
    $rand = '0' . $rand;
}
$rand .= rand();
if(!check_dir_exists($CFG->tempdir . '/backup')){
    $return['stage'] = 'createbackupfolder';
    $return['message'] = 'Couldn\'t create backup folder';
    echo json_encode($return);
    exit;
}
if(!$found->extract_to_pathname(get_file_packer(), $CFG->tempdir . '/backup/' . $rand)){
    $return['stage'] = 'extractbackup';
    $return['message'] = 'Couldn\'t extract backup file';
    echo json_encode($return);
    exit;
}

// Get or create category
$categoryname = 'LP Restore Course';
$categoryid = $DB->get_field('course_categories', 'id', array('name'=>$categoryname));
if (!$categoryid) {
    $categoryid = $DB->insert_record('course_categories', (object)array(
        'name' => $categoryname,
        'parent' => 0,
        'visible' => 0
    ));
    $DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
}

$shortname = 'LPR' . date('His');
$fullname = 'Learning Pool Restore ' . date('Y-m-d H:i:s');

// Create new course
$courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);

// Restore backup into course
// INTERACTIVE_NO indicates that a script is doing the backup
// TARGET_NEW_COURSE will create a new course from the backup
$controller = new restore_controller($rand, $courseid,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id,
        backup::TARGET_NEW_COURSE);
$controller->execute_precheck();
$controller->execute_plan();

// Set shortname and fullname from params
$DB->update_record('course', (object)array(
    'id' => $courseid,
    'shortname' => $courseshortname,
    'fullname' => $coursename,
    'summary' => $coursedesc
));

//get course sections to return
$sections = local_lp_coursewizard_get_sections($courseid);

$return['success'] = true;
$return['courseid'] = $courseid;
$return['sections'] = $sections;

echo json_encode($return);
exit;
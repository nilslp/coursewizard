<?php

require_once '../../../config.php';
require_once('../lib.php');

$type   = required_param('ajaxtype', PARAM_TEXT);
$sesskey = required_param('sesskey', PARAM_RAW);

if (!confirm_sesskey($sesskey)) {
    $results['success'] = false;
    $results['message'] = get_string('error:badsesskey','local_lp_coursewizard');
    echo json_encode($results);
    die;
}

$result = array();
switch ($type) {
    
    //module operations
    case 'updateresource':
        $moduleid                   = required_param('mid', PARAM_INT);
        $courseid                   = required_param('cid', PARAM_INT);
        $name                       = required_param('mod_name', PARAM_TEXT);
        $description                = required_param('mod_desc', PARAM_TEXT);
        $completion                 = required_param('completion', PARAM_INT);
        //scorm options
        $completionview             = optional_param('completionview', false, PARAM_BOOL);
        $completionstatuspass       = optional_param('completionstatuspass', false, PARAM_BOOL);
        $completionstatuscomplete   = optional_param('completionstatuscomplete', false, PARAM_BOOL);
        $completionscorerequired    = optional_param('completionscorerequired', false, PARAM_INT);
        
        $module = new lp_coursewizard_module($moduleid,$courseid);
        $result = $module->update_module($name, $description, $completion, $completionview, $completionstatuspass, $completionstatuscomplete, $completionscorerequired);
        echo json_encode($result);
        
        break;
    
    case 'getresourcedata':
        $moduleid                   = required_param('mid', PARAM_INT);
        $courseid                   = required_param('cid', PARAM_INT);
        $module = new lp_coursewizard_module($moduleid,$courseid);
        $result['success'] = true;
        $result['moddetails'] = $module->render_details();
        $result['modcompletion'] = $module->render_completion_options();
        
        echo json_encode($result);
        break;
    
    case 'getresourcelistitems':
        $courseid                   = required_param('cid', PARAM_INT);
        
        $course = new lp_coursewizard_course($courseid);
        $items = $course->render_resource_table(true);
        $result['success'] = true;
        $result['message'] = $items;
        
        echo json_encode($result);
        break;
    
    case 'setcompletionstatus':
        $courseid                   = required_param('cid', PARAM_INT);
        
        $course = new lp_coursewizard_course($courseid);
        
        $overall_aggregation        = required_param('oa', PARAM_INT);
        $activity_aggregation       = required_param('aa', PARAM_INT);
        $criteria_activity          = required_param('ca', PARAM_TEXT);
        $criteria_activity          = json_decode($criteria_activity);
        
        //put criteria activities in an assoc array
        $criteria_arr = array();
        foreach($criteria_activity as $ca){
            $criteria_arr[$ca->id] = $ca->value;
        }
        
        $data = new stdClass();
        $data->id = $courseid;
        $data->overall_aggregation = $overall_aggregation;
        $data->criteria_activity = $criteria_arr;
        $data->activity_aggregation = $activity_aggregation;
        
        //$res = local_lp_coursewizard_set_activity_completion_requirements($data);
        $res = $course->update_completion_requirements($data);
        if($res){
            $result['success'] = true;
            $result['message'] = 'Completion details saved.';
        }
        else{
            $result['success'] = false;
            $result['message'] = 'Unable to save completion details.';
        }
        echo json_encode($result);
        break;
    
    //enrolement
    case 'getenrolledusers':
        $courseid                   = required_param('cid', PARAM_INT);
        $course = new lp_coursewizard_course($courseid);
        
        $result = $course->render_users_table(true);
        echo json_encode($result);
        break;
    
    case 'getunenrolledusers':
        $courseid                   = required_param('cid', PARAM_INT);
        $course = new lp_coursewizard_course($courseid);
        
        $result = $course->render_users_table(false);
        echo json_encode($result);
        break;
    
    case 'enrolusers':
        $courseid                   = required_param('cid', PARAM_INT);
        $course = new lp_coursewizard_course($courseid);
        
        $userids = required_param('users', PARAM_TEXT);
        $result = $course->enrol_users($userids);
        echo json_encode($result);
        break;
    
    case 'publishcourse':
        $courseid                   = required_param('cid', PARAM_INT);
        $catid                      = required_param('catid', PARAM_INT);
        
        $course = new lp_coursewizard_course($courseid);
        $result = $course->publish($catid);
        echo json_encode($result);
        break;
}
exit;
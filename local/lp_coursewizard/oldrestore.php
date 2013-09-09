<?php

//function local_lp_coursewizard_upload_scorm($add, $type, $sectionreturn, $section, $course){
//    global $CFG,$DB;
//    require_once($CFG->libdir.'/filelib.php');
//    require_once($CFG->libdir.'/gradelib.php');
//    require_once($CFG->libdir.'/completionlib.php');
//    require_once($CFG->libdir.'/conditionlib.php');
//    require_once($CFG->libdir.'/plagiarismlib.php');
//    require_once($CFG->dirroot . '/course/modlib.php');
//
//    $course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);
//
//    list($module, $context, $cw) = can_add_moduleinfo($course, $add, $section);
//
//    $cm = null;
//
//    $data = new stdClass();
//    $data->section          = $section;  // The section number itself - relative!!! (section column in course_sections)
//    $data->visible          = $cw->visible;
//    $data->course           = $course->id;
//    $data->module           = $module->id;
//    $data->modulename       = $module->name;
//    $data->groupmode        = $course->groupmode;
//    $data->groupingid       = $course->defaultgroupingid;
//    $data->groupmembersonly = 0;
//    $data->id               = '';
//    $data->instance         = '';
//    $data->coursemodule     = '';
//    $data->add              = $add;
//    $data->return           = 0; //must be false if this is an add, go back to course view on cancel
//    $data->sr               = $sectionreturn;
//
//    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
//        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
//        file_prepare_draft_area($draftid_editor, null, null, null, null);
//        $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
//    }
//
//    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
//            and has_capability('moodle/grade:managegradingforms', $context)) {
//        require_once($CFG->dirroot.'/grade/grading/lib.php');
//
//        $data->_advancedgradingdata['methods'] = grading_manager::available_methods();
//        $areas = grading_manager::available_areas('mod_'.$module->name);
//
//        foreach ($areas as $areaname => $areatitle) {
//            $data->_advancedgradingdata['areas'][$areaname] = array(
//                'title'  => $areatitle,
//                'method' => '',
//            );
//            $formfield = 'advancedgradingmethod_'.$areaname;
//            $data->{$formfield} = '';
//        }
//    }
//
//    if (!empty($type)) { //TODO: hopefully will be removed in 2.0
//        $data->type = $type;
//    }
//    $return['success'] = true;
//    $return['message'] = 'File area prepared';
//    $return['contextid'] = $context->id;
//    $return['env'] = "filepicker";
//    $return['data'] = $data;
//    
//    return $return;
//}




//function local_lp_coursewizard_restore_course($sesskey){
//    
//    //some default settings
//    $catid=2;
//    $result = local_lp_coursewizard_restore_createtemp();
//    if($result['success']){
//        $contextid = $result['contextid'];
//        $result = local_lp_coursewizard_restore_extract($contextid,$result['filename']);
//        //details
//        if($result['success']){
//            $details = $result['details'];
//            $result = local_lp_coursewizard_restore_buildentry($contextid,$result['filepath'],$catid);
//            
//            if($result['success']){
//                $result = local_lp_coursewizard_restore_save($sesskey, $contextid, $result['restoreid'], $details);
//            }
//        }
//    }
//    return $result;
//}
//function local_lp_coursewizard_restore_createtemp(){
//    
//    global $CFG,$DB,$USER;
//    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
//    
//    $return = array();
//    $return['success'] = false;
//    $return['stage'] = 'createtemp';
//    $return['message'] = '';
//    
//    $filename = get_config('local_lp_coursewizard', 'templatelocation');
//    $table = 'files';
//    $result = $DB->get_record($table, array('filename'=>$filename,'mimetype'=>'application/vnd.moodle.backup'),'*');
//    $USER->id = 2;//Ensure admin user creates the backup so this is always true
//    $contextid = 2;//Set 2 as this is a course
//    $filecontextid = 5;//Set as 5 as it will always be saved by a user
//    if($result){
//        
//        list($context, $course, $cm) = get_context_info_array($contextid);
//        
//        // will be used when restore
//        if (!empty($filecontextid)) {
//            $filecontext = context::instance_by_id($filecontextid);
//        }
//        //get file browser
//        $browser = get_file_browser();
//        // check if tmp dir exists
//        $tmpdir = $CFG->tempdir . '/backup';
//        if (!check_dir_exists($tmpdir, true, true)) {
//            $return['message'] = 'Temp directory does not exist.';
//            return $return;
//        }
//        
//        if ($fileinfo = $browser->get_file_info($filecontext, $result->component, $result->filearea, $result->itemid, $result->filepath, $result->filename)) {
//            $filename = restore_controller::get_tempdir_name($course->id, $result->userid);
//            $pathname = $tmpdir . '/' . $filename;
//            $fileinfo->copy_to_pathname($pathname);
//            $return['success'] = true;
//            $return['message'] = 'File created ' . $filename;
//            $return['contextid'] = $contextid;
//            $return['filename'] = $filename;
//            return $return;
//        } else {
//            //file not found
//            $return['message'] = 'File not found at ' . $filename;
//            return $return;
//        }
//    }
//    else{
//        $return['message'] = 'File not found in files (DB)';
//        return $return;
//    }
//}
//function local_lp_coursewizard_restore_extract($contextid, $filename){
//    global $CFG;
//    
//    //set this value as the restore ues optional_param to check for filename
//    $_POST['filename'] = $filename;
//    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
//    
//    $return = array();
//    $return['success'] = false;
//    $return['stage'] = 'extract';
//    
//    $restore = new restore_ui_stage_confirm($contextid);
//    $outcome = $restore->process();
//    if($outcome){
//        $return['success'] = true;
//        $return['filepath'] = $restore->get_filepath();
//        $return['contextid'] = $restore->get_contextid();
//        $return['details'] = $restore->get_details();
//        $return['message'] = 'Files Extracted';
//    }
//    else{
//        $return['message'] = 'Error Extracting Files';
//    }
//    return $return;
//}
//function local_lp_coursewizard_restore_buildentry($contextid,$filepath,$catid,$stage=4,$target=2,$sesskey=1){
//    global $CFG,$USER;
//    
//    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
//    
//    $return = array();
//    $return['success'] = false;
//    $return['stage'] = 'buildentry';
//    
//    //set these value as the restore ues optional_param to check for filename
//    $_POST['filepath'] = $filepath;
//    $_POST['contextid'] = $contextid;
//    $_POST['stage'] = $stage;
//    $_POST['target'] = $target;
//    $_POST['targetid'] = $catid;
//    
//    $restore = new restore_ui_stage_destination($contextid);
//    if ($restore->process()) {
//        $rc = new restore_controller($restore->get_filepath(), $restore->get_course_id(), backup::INTERACTIVE_YES,
//                            backup::MODE_GENERAL, $USER->id, $restore->get_target());
//    }
//    if ($rc) {
//        // check if the format conversion must happen first
//        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
//            $rc->convert();
//        }
//        $restore = new restore_ui($rc, array('contextid'=>$contextid));
//    }
//    $outcome = $restore->process();
//    if (!$restore->is_independent()) {
//        if ($restore->get_stage() == restore_ui::STAGE_PROCESS && !$restore->requires_substage()) {
//            try {
//                $restore->execute();
//            } catch(Exception $e) {
//                $restore->cleanup();
//                $return['message'] = $e->getMessage();
//                return $return;
//            }
//        } else {
//            $restore->save_controller();
//        }
//    }
//    $return['success'] = true;
//    $return['message'] = 'Entry Created';
//    $return['restoreid'] = $rc->get_restoreid();
//    
//    return $return;
//}
//function local_lp_coursewizard_restore_save($sesskey, $contextid, $restoreid, $details){
//    global $CFG;
//    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
//    
//    $return = array();
//    $return['success'] = false;
//    $return['stage'] = 'savecourse';
//    
//    //clean up post
//    if(isset($_POST['filepath']))unset($_POST['filepath']);
//    if(isset($_POST['filename']))unset($_POST['filename']);
//    if(isset($_POST['ajaxtype']))unset($_POST['ajaxtype']);
//    if(isset($_POST['contextid']))unset($_POST['contextid']);
//    if(isset($_POST['stage']))unset($_POST['stage']);
//    if(isset($_POST['target']))unset($_POST['target']);
//    if(isset($_POST['targetid']))unset($_POST['targetid']);
//    if(isset($_POST['cname']))unset($_POST['cname']);
//    if(isset($_POST['cshortname']))unset($_POST['cshortname']);
//    
//    //add new post variables that would normally be submitted with the restore 
//    //form. This is needed as the restore classes check for post values so these
//    //need to be set manually.
//    $_POST['_qf__restore_review_form']                  = 1;
//    $_POST['restore']                                   = $restoreid;
//    $_POST['contextid']                                 = $contextid;
//    $_POST['stage']                                     = 16;
//    $_POST['sesskey']                                   = $sesskey;
//    $_POST['setting_course_course_fullname']            = $coursename;
//    $_POST['setting_course_course_shortname']           = $courseshortname;
//    $_POST['setting_course_course_startdate']           = $coursedate;
//    $_POST['setting_course_keep_groups_and_groupings']  = 1;
//    $_POST['setting_course_keep_roles_and_enrolments']  = 1;
//    $_POST['setting_course_overwrite_conf']             = 1;
//    $_POST['setting_root_activities']                   = 1;
//    $_POST['setting_root_blocks']                       = 1;
//    $_POST['setting_root_calendarevents']               = 1;
//    $_POST['setting_root_comments']                     = 1;
//    $_POST['setting_root_enrol_migratetomanual']        = 1;
//    $_POST['setting_root_filters']                      = 1;
//    $_POST['setting_root_grade_histories']              = 1;
//    $_POST['setting_root_logs']                         = 1;
//    $_POST['setting_root_role_assignments']             = 1;
//    $_POST['setting_root_users']                        = 1;
//    $_POST['setting_root_userscompletion']              = 1;
//    $_POST['submitbutton']                              = 'Perform restore';
//
//    //loop through the sections and add activities if exist
//    foreach ($details->sections as $key=>$section) {
//        // eg section_87_included
//        $included = $key.'_included';
//        $userinfo = $key.'_userinfo';
//        
//        if ($section->settings[$included]){
//            $_POST['setting_section_'.$included] = $section->settings[$included];
//            if ($section->settings[$userinfo]){
//                $_POST['setting_section_'.$userinfo] = $section->settings[$userinfo];
//            }
//        }
//        else{
//            continue;
//        }
//        
//        //add activity if it belongs to the section
//        foreach ($details->activities as $activitykey=>$activity) {
//            if ($activity->sectionid != $section->sectionid) {
//                continue;
//            } 
//            // eg scorm_47_included
//            $included = $activitykey.'_included';
//            $userinfo = $activitykey.'_userinfo';
//            
//            if ($activity->settings[$included]){
//                $_POST['setting_activity_'.$included] = $activity->settings[$included];
//                if ($activity->settings[$userinfo]){
//                    $_POST['setting_activity_'.$userinfo] = $activity->settings[$userinfo];
//                }
//            }
//        }
//    }
//    
//    $rc = restore_ui::load_controller($restoreid);
//    // check if the format conversion must happen first
//    if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
//        $rc->convert();
//    }
//    
//    $restore = new restore_ui($rc, array('contextid'=>$contextid));
//    $outcome = $restore->process();
//    if (!$restore->is_independent()) {
//        if ($restore->get_stage() == restore_ui::STAGE_PROCESS && !$restore->requires_substage()) {
//            try {
//                $restore->execute();
//            } catch(Exception $e) {
//                $restore->cleanup();
//                $return['message'] = $e->getMessage();
//                return $return;
//            }
//        } else {
//            $restore->save_controller();
//        }
//    }
//    $return['success'] = true;
//    $return['message'] = 'Course Saved';
//    $return['courseid'] = $rc->get_courseid();
//    return $return;
//}
?>

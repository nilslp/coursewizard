<?php
      // Admin-only code to delete a course utterly

    require_once('../../../config.php');
    require_once($CFG->dirroot . '/course/lib.php');
    require_once("common.php");
    
    $id     = required_param('id', PARAM_INT);              // course id
    $delete = optional_param('delete', '', PARAM_ALPHANUM); // delete confirmation hash

    $PAGE->set_url('/confirmdelete/confirmcoursedelete.php', array('id' => $id));
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    
    require_login();

    $site = get_site();

    $strdeletecourse = get_string("deletecourse");
    $stradministration = get_string("administration");
    $strcategories = get_string("categories");

    $course = null;
    
    if (! $course = $DB->get_record("course", array("id"=>$id))) {
        print_error("invalidcourseid", 'error', '', $id);
    }
    if ($site->id == $course->id) {
        // can not delete frontpage!
        print_error("invalidcourseid", 'error', '', $id);
    }

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    if (!can_delete_course($id)) {
        print_error('cannotdeletecourse');
    }

    $category = $DB->get_record("course_categories", array("id"=>$course->category));
    $courseshortname = format_string($course->shortname, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
    $categoryname = format_string($category->name, true, array('context' => get_context_instance(CONTEXT_COURSECAT, $category->id)));

    $PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php/'));
    $PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
    $PAGE->navbar->add($categoryname, new moodle_url('/course/category.php', array('id'=>$course->category)));
    if (! $delete) {
        $strdeletecheck = get_string("deletecheck", "", $courseshortname);
        $strdeletecoursecheck = get_string("deletecoursecheck");

        $PAGE->navbar->add($strdeletecheck);
        $PAGE->set_title("$site->shortname: $strdeletecheck");
        $PAGE->set_heading($site->fullname);
        
        $jsconfig = array(
                'name' => 'local_dlelegacytools',
                'fullpath' => '/local/dlelegacytools/confirmdelete/confirmdelete.js',
                'requires' => array(
                                'node',
                                'node-event-delegate',
                                'node-load',
                                'event',
                                'selector-css3',
                                'io-base',
                                'json-parse',
                                'event-hover',
                                'get',
                                'anim',
                                'panel'
                            )
                    );

        $PAGE->requires->js_init_call('M.local_dlelegacytools.init', null, false, $jsconfig);

        echo $OUTPUT->header();
    
        $message = "$strdeletecoursecheck<br /><br />" . format_string($course->fullname, true, array('context' => $coursecontext)) .  " (" . $courseshortname . ")";

        // Set up the details table
        $table_string = "<table class=\"generaltable boxaligncenter\" width=\"95%\" border=\"0\">";
        $table_string .= "<tr><th width=\"10%\">ID</th><th width=\"25%\">Type</th><th width=\"40%\">Name</th><th>Action</th></tr>";

        if (isset($course) && ($course->id > 0)) {
            // Get all course sections
            $sections = get_all_sections($course->id);
            
            foreach ($sections as $sec){
                if ($sec->sequence && $sec->sequence!=''){
                    $resources = explode(",",$sec->sequence);
                    foreach ($resources as $r){
                        $table_string .= process_item($r);
                    }
                }
            }	
        }
        else {
            $id = optional_param('id', 0, PARAM_INT);

            // Single item
            $table_string .= process_item($id);
        }

        $table_string .= "</table>";
        echo $OUTPUT->confirm($message . $table_string, "{$CFG->wwwroot}/course/delete.php?id=$course->id&delete=".md5($course->timemodified), "{$CFG->wwwroot}/course/category.php?id=$course->category");

        echo $OUTPUT->footer();
        exit;
    }

    if ($delete != md5($course->timemodified)) {
        print_error("invalidmd5");
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    // OK checks done, delete the course now.

    add_to_log(SITEID, "course", "delete", "view.php?id=$course->id", "$course->fullname (ID $course->id)");

    $strdeletingcourse = get_string("deletingcourse", "", $courseshortname);

    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title("$site->shortname: $strdeletingcourse");
    $PAGE->set_heading($site->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);

    delete_course($course);
    fix_course_sortorder(); //update course count in catagories

    echo $OUTPUT->heading( get_string("deletedcourse", "", $courseshortname) );

    echo $OUTPUT->continue_button("category.php?id=$course->category");

    echo $OUTPUT->footer();
    


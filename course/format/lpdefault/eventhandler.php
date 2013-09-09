<?php
function add_default_activities($course) {
	if($course->format == 'lpdefault'){
		global $DB,$CFG;
		if (!file_exists($CFG->dirroot.'/mod/certificate')){
		    // certificate mod is not installed
		    return true;
		}
		require_once($CFG->dirroot .'/mod/quiz/lib.php');
		require_once($CFG->dirroot .'/mod/forum/lib.php');
		require_once($CFG->dirroot .'/mod/certificate/lib.php');
		//will need to be changed if the layout order is altered
		$lpdefaultmodules = array('NONE','NONE','quiz','certificate','forum');
		
		foreach($lpdefaultmodules as $key=>$value){
			if($key==2||$key==3||$key==4){
				$module = new stdClass();
				$moduledata = $DB->get_record('modules', array('name'=>$value), '*', MUST_EXIST);
				$description = "This $moduledata->name has been generated on course creation.";
				//$module_type_index = 0;
				$module->introformat = FORMAT_MOODLE;
				$module->messageformat = FORMAT_MOODLE;
				switch ($value) {
					case 'certificate':
					$module->intro = $description;
					$module->type = 'certificate';
					$module->certificatetype = 'A4_non_embedded';
					$module->forcesubscribe = 0;
					$module->orientation = 'L';
					$module->datefmt = 1;
					$module->gradefmt = 1;
					break;
					case 'forum':
					$module->intro = $description;
					$module->type = 'forum';
					$module->forcesubscribe = 0;
					$module->format = 1;
					break;
					case 'quiz':
					$module->intro = $description;
					$module->feedbacktext = array(array('text'=>'Enter Text Here','format'=>'1'));
					$module->feedback = 1;
					$module->feedbackboundaries = array('');
					$module->grade = 100;
					$module->timeopen = 0;
					$module->timeclose = 0;
					$module->shufflequestions = false;
					$module->shuffleanswers = true;
					$module->quizpassword = '';
					$module->questionsperpage = 1;
					$module->reviewcorrectness = 4368;
					$module->reviewmarks = 4368;
					$module->reviewspecificfeedback = 4368;
					$module->reviewgeneralfeedback = 4368;
					$module->reviewrightanswer = 4368;
					$module->reviewoverallfeedback = 4368;
					$module->preferredbehaviour='deferredfeedback';
					break;
				}
				$module->name = ucfirst($moduledata->name);
				$module->introformat = 1;
				$module->course = $course->id;
				$module->section = $key;
				$module->module = $moduledata->id;
				$module->modulename = $moduledata->name;
				$module->add = $moduledata->name;
				$module->cmidnumber = '';
				$module->coursemodule = '';
				$add_instance_function = $moduledata->name . '_add_instance';

				$section = get_course_section($key, $course->id);
				$module->section = $section->id;
				$module->coursemodule = add_course_module($module);
				$module->section = $key;
				$module->instance = $add_instance_function($module, '');
				$DB->set_field('course_modules', 'instance', $module->instance, array('id'=>$module->coursemodule));
				
				add_mod_to_section($module);
				$module->cmidnumber = set_coursemodule_idnumber($module->coursemodule, '');

				rebuild_course_cache($course->id);

				$module_instance = $DB->get_field('course_modules', 'instance', array('id' => $module->coursemodule));
				$module_record = $DB->get_record($moduledata->name, array('id' => $module_instance));
				$module_record->instance = $module_instance;
			}
		}
	}
	return true;
}
?>

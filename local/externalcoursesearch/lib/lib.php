<?php

define('EXTSRCH_NAT','native');

include_once(dirname(__FILE__).'/../../shortlinker/lib/lib.php');

function get_courses_combined_search($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcount,$editing=false,$sources=array(EXTSRCH_NAT,'goodpractice')){
	$totalcounts = array();
	$results = array();
	
	if(count($sources)==0){
		return array();
	}
	
	$recordsperpage_temp = floor($recordsperpage/count($sources));
	
	if(in_array(EXTSRCH_NAT,$sources)){
		$totalcounts[EXTSRCH_NAT]=0;
		$results[EXTSRCH_NAT] = array_values(get_courses_search($searchterms,$sort,$page,$recordsperpage_temp,$totalcounts[EXTSRCH_NAT]));
		
		array_walk($results[EXTSRCH_NAT],'external_course_search_mark',EXTSRCH_NAT);
	}
	
	#$results = array_merge($results, search_external_sources($searchterms,$sort,$page,$recordsperpage,$totalcounts));
	//exclude external results when editing the page as they cannot currently be edited.
	if(!$editing){
		$results += search_external_sources($searchterms,$sort,$page,$recordsperpage_temp,$totalcounts,$sources);
	}
	
	$results_keys = array_keys($results);
	
	//get totals
	$totalcount = 0;
	$recall = false;
	foreach($results_keys as $rkey){
		$totalcount += $totalcounts[$rkey];
		if(empty($totalcounts[$rkey])){
			unset($sources[array_search($rkey,$sources)]);
			$sources=array_values($sources);
			$recall = true;
		}
	}
	
	if($recall){
		//recall with new sources
		return get_courses_combined_search($searchterms,$sort,$page,$recordsperpage,$totalcount,$editing,$sources);
	}
	
	$master_results = array();
	$run_combi = true;
	while($run_combi){
		$run_combi = false;
		foreach($results_keys as &$rkey){
			
			if($rkey == false){
				//previously marked as finished
				//set flag to exit the while loop
				//$run_combi = false;
				continue;
			}
			
			$cval = array_shift($results[$rkey]);
			
			if($cval === null){
				//We have reached the end of this array
				//mark it so it is not processed on the next loop
				//$run_combi = false;
				$rkey = false;
				continue;
			}
			
			//We still have results so ensure the flag continues the while loop
			$run_combi = true;
			$cval->source = $rkey;
			$master_results[] = $cval;
		}
	}
	
	return $master_results;
	
}

function search_external_sources($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcounts,$reg_sources=array('goodpractice')){
	
	//$reg_sources = array('goodpractice'); //@TODO configure this in the db
	$lib_dir = realpath(dirname(__FILE__)) . '/sources/';
	$rs_path='';
	$cls_t='';
	$clst=false;
	$results = array();
	if(!is_array($totalcounts)){
		$totalcounts = array();
	}
	
	foreach($reg_sources as $rsource){
		$rs_path = $lib_dir . $rsource . '.php';
		if(file_exists($rs_path)){
			include_once($rs_path);
			$cls_t = 'ext_source_'.$rsource;
			if(class_exists($cls_t)){
				$clst = new $cls_t();
				$totalcounts[$rsource] = 0;
				$results[$rsource] =  $clst->search($searchterms, $sort, $page, $recordsperpage,$totalcounts[$rsource]);
			}
		}
	}
	
	return $results;	
}

/**
 * Print a description of a course, suitable for browsing in a list.
 *
 * @param object $course the course object.
 * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
 */
function search_external_print_course($course, $highlightterms = '') {
    global $CFG, $USER, $DB, $OUTPUT;

	if($course->source === EXTSRCH_NAT){

	    $context = get_context_instance(CONTEXT_COURSE, $course->id);
	
	    // Rewrite file URLs so that they are correct
	    $course->summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);
		
	}

    echo html_writer::start_tag('div', array('class'=>'coursebox clearfix'));
    echo html_writer::start_tag('div', array('class'=>'info'));
    echo html_writer::start_tag('h3', array('class'=>'name'));

	if($course->source === EXTSRCH_NAT){
    	$linkhref = new moodle_url('/course/view.php', array('id'=>$course->id));
    	$coursename = get_course_display_name_for_list($course);
		$linktext = highlight($highlightterms, format_string($coursename));
	}else{
		$linkhref = $course->fullurl;
		$coursename = $course->fullname;
		$linktext = highlight($highlightterms, $coursename);
	}
	
    
    $linkparams = array('title'=>get_string('entercourse'));
    if (empty($course->visible)) {
        $linkparams['class'] = 'dimmed';
    }
    echo html_writer::link($linkhref, $linktext, $linkparams);
    echo html_writer::end_tag('h3');

    /// first find all roles that are supposed to be displayed
    if (!empty($CFG->coursecontact) && $course->source === EXTSRCH_NAT) {
        $managerroles = explode(',', $CFG->coursecontact);
        $namesarray = array();
        $rusers = array();

        if (!isset($course->managers)) {
            $rusers = get_role_users($managerroles, $context, true,
                'ra.id AS raid, u.id, u.username, u.firstname, u.lastname,
                 r.name AS rolename, r.sortorder, r.id AS roleid',
                'r.sortorder ASC, u.lastname ASC');
        } else {
            //  use the managers array if we have it for perf reasosn
            //  populate the datastructure like output of get_role_users();
            foreach ($course->managers as $manager) {
                $u = new stdClass();
                $u = $manager->user;
                $u->roleid = $manager->roleid;
                $u->rolename = $manager->rolename;

                $rusers[] = $u;
            }
        }

        /// Rename some of the role names if needed
        if (isset($context)) {
            $aliasnames = $DB->get_records('role_names', array('contextid'=>$context->id), '', 'roleid,contextid,name');
        }

        $namesarray = array();
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
        foreach ($rusers as $ra) {
            if (isset($namesarray[$ra->id])) {
                //  only display a user once with the higest sortorder role
                continue;
            }

            if (isset($aliasnames[$ra->roleid])) {
                $ra->rolename = $aliasnames[$ra->roleid]->name;
            }

            $fullname = fullname($ra, $canviewfullnames);
            $namesarray[$ra->id] = format_string($ra->rolename).': '.
                html_writer::link(new moodle_url('/user/view.php', array('id'=>$ra->id, 'course'=>SITEID)), $fullname);
        }

        if (!empty($namesarray)) {
            echo html_writer::start_tag('ul', array('class'=>'teachers'));
            foreach ($namesarray as $name) {
                echo html_writer::tag('li', $name);
            }
            echo html_writer::end_tag('ul');
        }

    }
    echo html_writer::end_tag('div'); // End of info div

    echo html_writer::start_tag('div', array('class'=>'summary'));
    $options = new stdClass();
    $options->noclean = true;
    $options->para = false;
    $options->overflowdiv = true;
    if (!isset($course->summaryformat)) {
        $course->summaryformat = FORMAT_MOODLE;
    }
    if($course->source === EXTSRCH_NAT){
    	echo highlight($highlightterms, format_text($course->summary, $course->summaryformat, $options,  $course->id));
		if ($icons = enrol_get_course_info_icons($course)) {
	        echo html_writer::start_tag('div', array('class'=>'enrolmenticons'));
	        foreach ($icons as $icon) {
	            echo $OUTPUT->render($icon);
	        }
	        echo html_writer::end_tag('div'); // End of enrolmenticons div
	    }
	}else{
		echo highlight($highlightterms, $course->summary);
		echo html_writer::start_tag('div', array('class'=>'enrolmenticons'));
		echo html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('extlink', 'local_externalcoursesearch'), 'title' => get_string('externalcourse', 'local_externalcoursesearch')));
		#echo "EXTERNAL";
		echo html_writer::end_tag('div'); // End of enrolmenticons div
	}
    
    echo html_writer::end_tag('div'); // End of summary div
    echo html_writer::end_tag('div'); // End of coursebox div
}

function external_course_search_mark(&$crs,$key,$src){
	 $crs->source = $src;
}

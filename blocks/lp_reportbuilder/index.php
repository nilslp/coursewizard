<?php // $Id$

/**
 * Page containing list of available reports and new report form
 */

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->libdir . '/adminlib.php');
    require_once($CFG->dirroot . "/blocks/lp_reportbuilder/lib.php");
    require_once('report_forms.php');

    $id = optional_param('id', null,PARAM_INT); // id for delete report
    $d = optional_param('d', false, PARAM_BOOL); // delete record?
    $em = optional_param('em', false, PARAM_BOOL); // embedded report?
    $confirm = optional_param('confirm', false, PARAM_BOOL); // confirm delete
  
    admin_externalpage_setup('managelearningpoolreports');
    echo $OUTPUT->header();
    
    global $USER, $OUTPUT, $DB,$PAGE;
    
    $jsconfig = array(
        'name' => 'block_lp_reportbuilder',
        'fullpath' => '/blocks/lp_reportbuilder/js/reports.behaviours.js',
        'requires' => array(
                        'node',
                        'event',
                        'selector-css3',
                        'event-hover',
                        'datatable',
                        'panel',
                        'dd-plugin',
                        'yui2-calendar'
                    ),
        'strings' => array(
                array('generatingreport', 'block_lp_reportbuilder')
            )
        );

    $PAGE->requires->js_init_call('M.block_lp_reportbuilder.init', null, false, $jsconfig);
  
    $returnurl = $CFG->wwwroot.'/blocks/lp_reportbuilder/index.php';
    $type = $em ? 'reload' : 'delete';

    // delete an existing report
    if ($d && $confirm) {
        if(!confirm_sesskey()) {
            echo $OUTPUT->notification(get_string('error:unknownbuttonclicked', 'block_lp_reportbuilder'), 'notifyfailure');
        }
        if(delete_report($id)) {
            add_to_log(SITEID, 'reportbuilder', $type . ' report', 'index.php', 'Report ID=' . $id);
            echo $OUTPUT->notification(get_string($type . 'report', 'block_lp_reportbuilder'), 'notifysuccess');
        } 
        else {
            echo $OUTPUT->notification(get_string('no' . $type . 'report', 'block_lp_reportbuilder'), 'notifyfailure');
        }
    } else if ($d) {        
        echo $OUTPUT->heading(get_string('reportbuilder','block_lp_reportbuilder'));

        if ($em) {        	
            echo $OUTPUT->confirm(get_string('reportconfirm'.$type,'block_lp_reportbuilder'),"index.php?id={$id}&amp;d=1&amp;em={$em}&amp;confirm=1&amp;sesskey={$USER->sesskey}", $returnurl);
        } else {
            echo $OUTPUT->confirm(get_string('reportconfirm'.$type,'block_lp_reportbuilder'),"index.php?id={$id}&amp;d=1&amp;em={$em}&amp;confirm=1&amp;sesskey={$USER->sesskey}", $returnurl);
        }
        echo $OUTPUT->footer();

        die;
    }

    // form definition
    $mform = new report_builder_new_form();

    // form results check
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    }
    if ($fromform = $mform->get_data()) {
        if(empty($fromform->submitbutton)) {
            echo $OUTPUT->notification('error:unknownbuttonclicked', 'notifyfailure');
        }
        
        // Create new record here
        $todb = new object();
        $todb->fullname = $fromform->fullname;
        $todb->shortname = reportbuilder::create_shortname($fromform->fullname);
        $todb->source = ($fromform->source != '0') ? $fromform->source : null;
        $todb->hidden = $fromform->hidden;
        $todb->recordsperpage = 40;
        $todb->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $todb->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY; // default to limited access
        $todb->embedded = 0;
        $todb->showinblock =$fromform->showinblock;

        $transaction = $DB->start_delegated_transaction();
                
        if($newid = $DB->insert_record('report_builder',$todb)) {
            add_to_log(SITEID, 'reportbuilder', 'new report', 'report.php?id='.$newid, $fromform->fullname . ' (ID=' . $newid . ')');
        } 
        else {
            $transaction->rollback(new Exception('Could not create new report'));

            redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
        }

        // if administrator or admin role exists, restrict access to new report to administrators only
        // (if role doesn't exist report will not be visible to anyone)
        if($adminroleid = $DB->get_field('role', 'id', array('shortname' => 'administrator')) ||
           $adminroleid = $DB->get_field('role', 'id', array('shortname' => 'admin'))) {
            $todb = new object();
            $todb->reportid = $newid;
            $todb->type = 'role_access';
            $todb->name = 'enable';
            $todb->value = 1;

            $todb2 = new object();
            $todb2->reportid = $newid;
            $todb2->type = 'role_access';
            $todb2->name = 'activeroles';
            $todb2->value = $adminroleid;

            if(!$DB->insert_record('report_builder_settings', $todb) || !$DB->insert_record('report_builder_settings', $todb2)) {
                $transaction->rollback(new Exception('Could not create new report'));
                
                redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
            }
        }

        // create columns for new report based on default columns
        $src = reportbuilder::get_source_object($fromform->source);
        
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $defaultcolumns = $src->defaultcolumns;
            $so = 1;
            foreach($defaultcolumns as $option) {
                try {
                    $heading = isset($option['heading']) ? $option['heading'] :
                        null;
                    $column = $src->new_column_from_option($option['type'],
                        $option['value'], $heading);

                    $todb = new object();
                    $todb->reportid = $newid;
                    $todb->type = addslashes($column->type);
                    $todb->value = addslashes($column->value);
                    //$todb->heading = addslashes($column->heading);
                    $todb->heading = $column->heading;
                    $todb->hidden = addslashes($column->hidden);
                    $todb->sortorder = $so;
                    
                    if(!$DB->insert_record('report_builder_columns', $todb)) {
                        $transaction->rollback(new Exception('Could not create new report'));
                        redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
                    }
                    $so++;
                }
                catch (ReportBuilderException $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }

        // create filters for new report based on default filters
        $src = reportbuilder::get_source_object($fromform->source);
        
        if (isset($src->defaultfilters) && is_array($src->defaultfilters)) {
            $defaultfilters = $src->defaultfilters;
            $so = 1;
            foreach($defaultfilters as $option) {
                try {
                    $advanced = isset($option['advanced']) ? $option['advanced'] :
                        null;
                    $filter = $src->new_filter_from_option($option['type'],
                        $option['value'], $advanced);

                    $todb = new object();
                    $todb->reportid = $newid;
                    $todb->type = addslashes($filter->type);
                    $todb->value = addslashes($filter->value);
                    $todb->advanced = addslashes($filter->advanced);
                    $todb->sortorder = $so;
                    
                    if(!$DB->insert_record('report_builder_filters', $todb)) {
                        $transaction->rollback(new Exception('Could not create report'));

                        redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
                    }
                    $so++;
                }
                catch (ReportBuilderException $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        
        $transaction->allow_commit();
        
        redirect($CFG->wwwroot.'/blocks/lp_reportbuilder/general.php?id=' . $newid);
    }

    // add new report button
	// echo html_writer::link('#','+ Add New Report Thingy',array('style'=>'text-align:right;','id'=>'add-report','class'=>'button'));
	echo html_writer::tag('button','[+] Add New Report',array('style'=>'text-align:right;','id'=>'add-report','class'=>'add'));
    
    echo $OUTPUT->heading(get_string('usergeneratedreports','block_lp_reportbuilder'));

    $tableheader = array(get_string('name','block_lp_reportbuilder'),
                         get_string('source','block_lp_reportbuilder'),
                         get_string('options','block_lp_reportbuilder'));

    // Only get non-embedded reports    
    $reports = $DB->get_records('report_builder', array('embedded' => 0), 'fullname');
    
    if ($reports) {
        $data = array();
        foreach($reports as $report) {        	
            $cells = array();
            $viewurl = reportbuilder_get_report_url($report);
                        
            $src = reportbuilder::get_source_object($report->source);
            
            $strsettings = get_string('settings','block_lp_reportbuilder');
            $strdelete = get_string('delete','block_lp_reportbuilder');
            $strviewsaved = get_string('savedsearches','block_lp_reportbuilder');
  
            $settings = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/general.php?id='.$report->id.'" title="'.$strsettings.'">'
                        . '<img src="'.$OUTPUT->pix_url('t/edit', 'core').'" alt="'.$strsettings.'" /></a>';
            $delete = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/index.php?d=1&amp;id='.$report->id.'" title="'.$strdelete.'">' 
                        . '<img src="'.$OUTPUT->pix_url('t/delete', 'core').'" alt="'.$strdelete.'" /></a>';            
            $viewsaved = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/savedsearches.php?id='.$report->id.'" title="'.$strviewsaved.'">' 
                        . '<img src="'.$OUTPUT->pix_url('t/preview', 'core').'" alt="'.$strviewsaved.'" /></a>';
                                    
            $cells[] = new html_table_cell('<a href="'.$viewurl.'">'.$report->fullname.'</a>');           
            $cells[] = new html_table_cell($src->sourcetitle);;
            $cells[] = new html_table_cell("$settings &nbsp; $delete &nbsp; $viewsaved");
             
            $row = new html_table_row($cells);          
            
            $data[] = $row;
        }
   
		// display mform
    	$mform->display();
        
        // Output the user generated report table
        $reportstable = new html_table();
        $reportstable->summary = '';
        $reportstable->attributes = array('class'=>'berocca');
        $reportstable->head = $tableheader;
        $reportstable->data = $data;
        
        echo html_writer::table($reportstable);
    } 
    else {
    	
        print get_string('noreports','block_lp_reportbuilder');
        $mform->display();
    }
    
    echo $OUTPUT->heading(get_string('embeddedreports','block_lp_reportbuilder'));

    $embeds = reportbuilder_get_all_embedded_reports();
    // Get a list of existing embedded reports and their IDs (outside the loop for efficiency)
    $embedded_ids = $DB->get_records_menu('report_builder', array('embedded' => 1), 'id', 'id, shortname');

    $data = array();

    if (is_array($embeds) && count($embeds) > 0) {
        $strsettings = get_string('settings','block_lp_reportbuilder');
        $strreload = get_string('restoredefaults','block_lp_reportbuilder');

        foreach($embeds as $embed) {
            $id = reportbuilder_get_embedded_id_from_shortname($embed->shortname, $embedded_ids);
            $fullname = $embed->fullname;
            $shortname = $embed->shortname;
            $url = $embed->url;
            $strviewsaved = get_string('savedsearches','block_lp_reportbuilder');
            
            $settings = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/general.php?id='.$id.'" title="'.$strsettings.'">' .
                '<img src="'.$OUTPUT->pix_url('t/edit', 'core').'" alt="'.$strsettings.'" /></a>';
            $reload = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/index.php?em=1&amp;d=1&amp;id='.$id.'" title="'.$strreload.'">' .
                '<img src="'.$OUTPUT->pix_url('i/backup', 'core').'" alt="'.$strreload.'" /></a>';     
            $viewsaved = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/savedsearches.php?id='.$id.'" title="'.$strviewsaved.'">' 
                        . '<img src="'.$OUTPUT->pix_url('t/preview', 'core').'" alt="'.$strviewsaved.'" /></a>';
     
            $src = reportbuilder::get_source_object($embed->source);
            
            $cells = array();
            
            $cells[] = new html_table_cell('<a href="'.$CFG->wwwroot . $url .'">' . $fullname . '</a>');
            $cells[] = new html_table_cell($src->sourcetitle);
            $cells[] = new html_table_cell("$settings &nbsp; $reload &nbsp; $viewsaved");
            
            $row = new html_table_row($cells);
            
            $data[] = $row;
        }
        
        // Output the embedded report table
        $embeddedreportstable = new html_table();
        $embeddedreportstable->summary = '';
        $embeddedreportstable->attributes = array('class'=>'berocca');
        $embeddedreportstable->head = $tableheader;
        $embeddedreportstable->data = $data;
        
        echo html_writer::table($embeddedreportstable);
    } 
    else {
        print get_string('noembeddedreports','block_lp_reportbuilder');
    }
    
    print '<br /><br />';

    echo $OUTPUT->footer();

// page specific functions

/**
 * Deletes a report and any associated data
 *
 * @param integer $id ID of the report to delete
 *
 * @return boolean True if report was successfully deleted
 */
function delete_report($id) {
    global $DB;
    
    if(!$id) {
        return false;
    }

    $transaction = $DB->start_delegated_transaction();
    
    // delete the report
    if(!$DB->delete_records('report_builder', array('id' => $id))) {
    	$transaction->rollback(new Exception('report_builder error'));

    	return false;
    }
    // delete any columns
    if(!$DB->delete_records('report_builder_columns', array('reportid' => $id))) {
        $transaction->rollback(new Exception('report_builder_columns error'));
    	
        return false;
    }
    // delete any filters
    if(!$DB->delete_records('report_builder_filters', array('reportid' => $id))) {
        $transaction->rollback(new Exception('report_builder_filters error'));

        return false;
    }
    // delete any content and access settings
    if(!$DB->delete_records('report_builder_settings', array('reportid' => $id))) {
        $transaction->rollback(new Exception('report_builder_settings error'));
    	
        return false;
    }
    // delete any saved searches
    if(!$DB->delete_records('report_builder_saved', array('reportid' => $id))) {
        $transaction->rollback(new Exception('report_builder_saved error'));
    	
        return false;
    }

    // all okay commit changes
    $transaction->allow_commit();

    return true;
}
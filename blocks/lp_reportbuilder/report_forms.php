<?php

/**
 * Moodle Formslib templates for report builder settings forms
 */

require_once "$CFG->dirroot/lib/formslib.php";
include_once($CFG->dirroot.'/blocks/lp_reportbuilder/classes/rb_base_content.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
 * Formslib template for the new report form
 */
class report_builder_new_form extends moodleform {

    function definition() {

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('newreport', 'block_lp_reportbuilder'));
        $sources = reportbuilder::get_source_list();
     
        if(count($sources)>0) {

            $mform->addElement('text', 'fullname', get_string('reportname', 'block_lp_reportbuilder'), 'maxlength="255"');
            $mform->setType('fullname', PARAM_TEXT);
            $mform->addRule('fullname',null,'required');
            //$mform->setHelpButton('fullname', array('reportbuilderfullname',get_string('reportname','block_lp_reportbuilder'),'block_lp_reportbuilder'));

            $pick = array(0 => get_string('selectsource','block_lp_reportbuilder'));
            $select = array_merge($pick, $sources);
            $mform->addElement('select','source', get_string('source','block_lp_reportbuilder'), $select);
            // invalid if not set
            $mform->addRule('source', get_string('error:mustselectsource','block_lp_reportbuilder'), 'regex','/[^0]+/');
            //$mform->setHelpButton('source', array('reportbuildersource',get_string('source','block_lp_reportbuilder'),'block_lp_reportbuilder'));

            $mform->addElement('advcheckbox','hidden', get_string('hidden','block_lp_reportbuilder'), '', null, array(0,1));
            
            $mform->addElement('advcheckbox', 'showinblock', get_string('showinblock','block_lp_reportbuilder'), '', null, array(0,1));
            $mform->setDefault('showinblock', true);
            //$mform->setHelpButton('hidden', array('reportbuilderhidden',get_string('hidden','block_lp_reportbuilder'),'block_lp_reportbuilder'));
            $this->add_action_buttons(true, get_string('createreport', 'block_lp_reportbuilder'));

        } else {
            $mform->addElement('html', get_string('error:nosources','block_lp_reportbuilder'));
        }
        
        $mform->updateAttributes(array('class'=>'mform hide'));
    }

}


/**
 * Formslib tempalte for the edit report form
 */
class report_builder_edit_form extends moodleform {
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];

        $mform->addElement('header', 'general', get_string('reportsettings', 'block_lp_reportbuilder'));

        $mform->addElement('text', 'fullname', get_string('reporttitle','block_lp_reportbuilder'), array('size'=>'30'));
        $mform->setDefault('fullname', $report->fullname);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname',null,'required');
        //$mform->setHelpButton('fullname', array('reportbuilderfullname',get_string('reporttitle','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setDefault('description', $report->description);
        $mform->setType('description', PARAM_CLEAN);
        //$mform->setHelpButton('description', array('reportbuilderdescription', get_string('description'),'block_lp_reportbuilder'));

        $mform->addElement('static', 'reportsource', get_string('source','block_lp_reportbuilder'), $report->source);
        //$mform->setHelpButton('reportsource', array('reportbuildersource',get_string('source','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        $mform->addElement('advcheckbox', 'hidden', get_string('hidden','block_lp_reportbuilder'), '', null, array(0,1));
        $mform->setType('hidden', PARAM_INT);
        $mform->setDefault('hidden', $report->hidden);
        
        $mform->addElement('advcheckbox', 'showinblock', get_string('showinblock','block_lp_reportbuilder'), '', null, array(0,1));
        $mform->setType('showinblock', PARAM_INT);
        $mform->setDefault('showinblock', $report->showinblock);
        
        // Only embedded reports can be linked to from the My Reports block
        if (!$report->is_embedded) {
            $mform->hardFreeze('showinblock');
        }
        
        //$mform->setHelpButton('hidden', array('reportbuilderhidden',get_string('hidden','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        $mform->addElement('text', 'recordsperpage', get_string('recordsperpage','block_lp_reportbuilder'), array('size'=>'6'));
        $mform->setDefault('recordsperpage', $report->recordsperpage);
        $mform->setType('recordsperpage', PARAM_INT);
        $mform->addRule('recordsperpage',null,'numeric');
        //$mform->setHelpButton('recordsperpage', array('reportbuilderrecordsperpage',get_string('recordsperpage','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        $reporttype = ($report->embeddedurl === null) ? get_string('usergenerated','block_lp_reportbuilder') :
            get_string('embedded', 'block_lp_reportbuilder');

        $mform->addElement('static', 'reporttype', get_string('reporttype', 'block_lp_reportbuilder'), $reporttype);

        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','source',$report->source);
        $mform->setType('source', PARAM_TEXT);
        $this->add_action_buttons();
    }

}

/**
 * Formslib template for the global settings form
 */
class report_builder_global_settings_form extends moodleform {
    function definition() {
        global $CFG, $REPORT_BUILDER_EXPORT_OPTIONS, $REPORT_BUILDER_PERMISSIONS_DEFAULTS;
        $mform =& $this->_form;

        $mform->addElement('header', 'settings', get_string('globalsettings', 'block_lp_reportbuilder'));

        $exportoptions = get_config('reportbuilder', 'exportoptions');

        $group = array();
       // $oauthenabled = get_config('local_oauth', 'oauthenabled');
        $sitecontext = get_context_instance(CONTEXT_SYSTEM);
       // $oauthcap = has_capability('local/oauth:negotiate', $sitecontext);
        foreach($REPORT_BUILDER_EXPORT_OPTIONS as $option => $code) {
            // specific checks for fusion tables export
//            if ($option == 'fusion' && (!$oauthenabled || !$oauthcap)) {
//                continue;
//            }

            $group[] =& $mform->createElement('checkbox', 'export'.$option, '', get_string('export'.$option,'block_lp_reportbuilder'));
            if($exportoptions) {
                // bitwise operation to see if bit for this export
                // option is set
                if(($exportoptions & $code) == $code) {
                    $mform->setDefault('export'.$option, 1);
                } else {
                    $mform->setDefault('export'.$option, 0);
                }
            }
        }
        $mform->addGroup($group, 'exportoptions', get_string('exportoptions','block_lp_reportbuilder'), '<br />', false);

        $defaultreportpermissions = get_config('reportbuilder', 'defaultreportpermissions');
        $mform->addElement('select', 'defaultreportpermissions', get_string('defaultpermissions', 'block_lp_reportbuilder'), $REPORT_BUILDER_PERMISSIONS_DEFAULTS);

        if ($defaultreportpermissions) {
            $mform->setDefault('defaultreportpermissions', $defaultreportpermissions);
        }
        
        $allowdeletedusers = get_config('reportbuilder', 'allowdeletedusers');
        $mform->addElement('advcheckbox', 'allowdeletedusers', get_string('allowdeletedusers','block_lp_reportbuilder'), '', null, array(0,1));
         
        if ($allowdeletedusers) {
            $mform->setDefault('allowdeletedusers', $allowdeletedusers);
        }
        
        $showcoursesearchhierarchyfilter = get_config('reportbuilder', 'showcoursesearchhierarchyfilter');
        $mform->addElement('advcheckbox', 'showcoursesearchhierarchyfilter', get_string('showcoursesearchhierarchyfilter','block_lp_reportbuilder'), '', null, array(0,1));
 
        $defaultdateformat = get_config('reportbuilder', 'defaultdateformat');
        $mform->addElement('text', 'defaultdateformat', get_string('defaultdateformat', 'block_lp_reportbuilder'), $defaultdateformat);
        $mform->addHelpButton('defaultdateformat', 'defaultdateformat', 'block_lp_reportbuilder');
        $mform->setDefault('defaultdateformat', $defaultdateformat);
        
        $defaultdatetimeformat = get_config('reportbuilder', 'defaultdatetimeformat');
        $mform->addElement('text', 'defaultdatetimeformat', get_string('defaultdatetimeformat', 'block_lp_reportbuilder'), $defaultdatetimeformat);        
        $mform->addHelpButton('defaultdatetimeformat', 'defaultdatetimeformat', 'block_lp_reportbuilder');
        $mform->setDefault('defaultdatetimeformat', $defaultdatetimeformat);
        
        $defaulttimeformat = get_config('reportbuilder', 'defaulttimeformat');
        $mform->addElement('text', 'defaulttimeformat', get_string('defaulttimeformat', 'block_lp_reportbuilder'), $defaulttimeformat);        
        $mform->addHelpButton('defaulttimeformat', 'defaulttimeformat', 'block_lp_reportbuilder');
        $mform->setDefault('defaulttimeformat', $defaulttimeformat);        
        
        $mform->addElement('header', 'settings', get_string('coursequizquicksettings', 'block_lp_reportbuilder'));

        $showcoursequizquick = get_config('reportbuilder', 'showcoursequizquick');
        $mform->addElement(
                'select',
                'showcoursequizquick',
                get_string('showcoursequizquick', 'block_lp_reportbuilder'),
                array(
                    0 => get_string('quizquickoff','block_lp_reportbuilder'),
                    1 => get_string('quizquickquiz','block_lp_reportbuilder'),
                    2 => get_string('quizquickscorm','block_lp_reportbuilder')
                )
            );

        if ($showcoursequizquick) {
            $mform->setDefault('showcoursequizquick', $showcoursequizquick);
        }
        
        // essex report format
        $coursequizquickformat = get_config('reportbuilder', 'coursequizquickformat');
        $mform->addElement(
                'select',
                'coursequizquickformat',
                get_string('coursequizquickformat', 'block_lp_reportbuilder'),
                array(
                    0 => get_string('completeincompletena','block_lp_reportbuilder'),
                    1 => get_string('attemptednotattempted','block_lp_reportbuilder'),
                    2 => get_string('completeexclusions','block_lp_reportbuilder'),
                    3 => get_string('alldata','block_lp_reportbuilder'),
                )
            );

        if ($coursequizquickformat) {
            $mform->setDefault('coursequizquickformat', $coursequizquickformat);
        }
        
        $includemodulename = get_config('reportbuilder', 'includemodulename');
        $mform->addElement('advcheckbox', 'includemodulename', get_string('includemodulename','block_lp_reportbuilder'), '', null, array(0,1));
        $mform->addHelpButton('includemodulename', 'includemodulename', 'block_lp_reportbuilder');
         
        if ($includemodulename) {
            $mform->setDefault('includemodulename', $includemodulename);
        }
        
         // extra report fields
        $extrareportfields = get_config('reportbuilder', 'extrareportfields');
        $mform->addElement(
                'text',
                'extrareportfields',
                get_string('extrareportfields', 'block_lp_reportbuilder'),
                $extrareportfields
            );

        if ($extrareportfields) {
            $mform->setDefault('extrareportfields', $extrareportfields);
        }
               
        $showcustomtrentdownload = get_config('reportbuilder', 'showcustomtrentdownload');
        $mform->addElement('advcheckbox', 'showcustomtrentdownload', get_string('showcustomtrentdownload','block_lp_reportbuilder'), '', null, array(0,1));
         
        if ($showcustomtrentdownload) {
            $mform->setDefault('showcustomtrentdownload', $showcustomtrentdownload);
        }
        
        // Skills Checker settings
        $mform->addElement('header', 'settings', get_string('skillscheckersettings', 'block_lp_reportbuilder'));

        $enableskillschecker = get_config('reportbuilder', 'enableskillschecker');
        $mform->addElement('advcheckbox', 'enableskillschecker', get_string('skillscheckerenable','block_lp_reportbuilder'), '', null, array(0,1));
       
        if ($enableskillschecker) {
            $mform->setDefault('enableskillschecker', $enableskillschecker);
        }
        
        $skillscheckscorm = get_config('reportbuilder', 'skillscheckscorm');        
        $mform->addElement('text', 'skillscheckscorm', get_string('skillscheckscorm', 'block_lp_reportbuilder'), $skillscheckscorm);
        
        if (isset($skillscheckscorm)) {
            $mform->setDefault('skillscheckscorm', $skillscheckscorm);
        }
        
        $skillschecksco = get_config('reportbuilder', 'skillschecksco');        
        $mform->addElement('text', 'skillschecksco', get_string('skillschecksco', 'block_lp_reportbuilder'), $skillschecksco);
        
        if (isset($skillscheckscorm)) {
            $mform->setDefault('skillschecksco', $skillschecksco);
        }
        
        $skillscheckstartmonth = get_config('reportbuilder', 'skillscheckstartmonth');
        $mform->addElement('text', 'skillscheckstartmonth', get_string('skillscheckstartmonth', 'block_lp_reportbuilder'), 'maxlength="6"');
        
        if (isset($skillscheckstartmonth)) {
            $mform->setDefault('skillscheckstartmonth', $skillscheckstartmonth);
        }
        
        $context = context_system::instance();
        if (has_capability('moodle/site:config', $context)) {
            // Misc settings
            $mform->addElement('header', 'gensettings', get_string('general'));

            $runhistoriccron = get_config('block_lp_reportbuilder', 'runhistoriccron');
            $mform->addElement('advcheckbox', 'runhistoriccron', get_string('runhistoriccron','block_lp_reportbuilder'), '', null, array(0,1));
            $mform->addHelpButton('runhistoriccron', 'runhistoriccron', 'block_lp_reportbuilder');

            if (isset($runhistoriccron)) {
                $mform->setDefault('runhistoriccron', $runhistoriccron);
            }
            
            $usesimplehierarchyselect = get_config('block_lp_reportbuilder', 'usesimplehierarchyselect');
            $mform->addElement('advcheckbox', 'usesimplehierarchyselect', get_string('usesimplehierarchyselect','block_lp_reportbuilder'), '', null, array(0,1));
            $mform->addHelpButton('usesimplehierarchyselect', 'usesimplehierarchyselect', 'block_lp_reportbuilder');

            if (isset($usesimplehierarchyselect)) {
                $mform->setDefault('usesimplehierarchyselect', $usesimplehierarchyselect);
            }
        }
        
        
        $this->add_action_buttons();
    }

}


/**
 * Formslib template for edit filters form
 */
class report_builder_edit_filters_form extends moodleform {
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];

        $mform->addElement('header', 'searchoptions', get_string('searchoptions', 'block_lp_reportbuilder'));

       // $mform->setHelpButton('searchoptions', array('reportbuilderfilters',get_string('filters','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        $strmovedown = get_string('movedown','block_lp_reportbuilder');
        $strmoveup = get_string('moveup','block_lp_reportbuilder');
        $strdelete = get_string('delete','block_lp_reportbuilder');
        $spacer = '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" />';

        if(isset($report->filteroptions) && is_array($report->filteroptions) && count($report->filteroptions)>0) {
            $mform->addElement('html','<div>'.get_string('help:searchdesc','block_lp_reportbuilder').'</div><br />');

            $mform->addElement('html', '<div class="reportbuilderform"><table><tr><th>'.get_string('searchfield','block_lp_reportbuilder').
                '</th><th>'.get_string('advanced','block_lp_reportbuilder').'</th><th>'.get_string('options','block_lp_reportbuilder').'</th><tr>');

            $filtersselect = $report->get_filters_select();

            if(isset($report->filters) && is_array($report->filters) && count($report->filters)>0) {
                $filters = $report->filters;
                $filtercount = count($filters);
                $i = 1;
                foreach($filters as $index => $filter) {
                    $row = array();
                    $type = $filter->type;
                    $value = $filter->value;
                    $field = "{$type}-{$value}";
                    $advanced = $filter->advanced;
                    $fid = $index;

                    $mform->addElement('html','<tr fid="'.$fid.'"><td>');
                    $mform->addElement('selectgroups', "filter{$fid}", '', $filtersselect, array('class'=>'filter_selector'));
                    $mform->setDefault("filter{$fid}", $field);
                    $mform->addElement('html','</td><td>');
                    $mform->addElement('checkbox',"advanced{$fid}",'');
                    $mform->setDefault("advanced{$fid}",$advanced);

                    $mform->addElement('html','</td><td>');
                    $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/filters.php?d=1&amp;id='.$id.'&amp;fid='.$fid.'" title="'.$strdelete.'" class="deletefilterbtn"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a>');
                    if($i != 1) {
                        $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/filters.php?m=up&amp;id='.$id.'&amp;fid='.$fid.'" title="'.$strmoveup.'" class="movefilterupbtn"><img src="'.$CFG->wwwroot.'/pix/t/up.gif" class="iconsmall" alt="'.$strmoveup.'" /></a>');
                    } else {
                        $mform->addElement('html', $spacer);
                    }
                    if($i != $filtercount) {
                        $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/filters.php?m=down&amp;id='.$id.'&amp;fid='.$fid.'" title="'.$strmovedown.'" class="movefilterdownbtn"><img src="'.$CFG->wwwroot.'/pix/t/down.gif" class="iconsmall" alt="'.$strmovedown.'" /></a>');
                    } else {
                        $mform->addElement('html', $spacer);
                    }
                    $mform->addElement('html','</td></tr>');
                    $i++;
                }
            } else {
                $mform->addElement('html','<p>'. get_string('nofiltersyet','block_lp_reportbuilder').'</p>');
            }


            $mform->addElement('html','<tr><td>');
            $newfilterselect = array_merge(
                array(
                    get_string('new') => array(0 => get_string('addanotherfilter','block_lp_reportbuilder'))
                ),
                $filtersselect);
                
              // Remove already-added filters from the new filter selector
            $cleanedfilterselect = $newfilterselect;
            foreach ($newfilterselect as $okey => $optgroup) {
                foreach ($optgroup as $typeval => $heading) {
                    $typevalarr = explode('-', $typeval);
                    foreach ($report->filters as $curfilter) {
                        if ($curfilter->type == $typevalarr[0] && $curfilter->value == $typevalarr[1]) {
                            unset($cleanedfilterselect[$okey][$typeval]);
                        }
                    }
                }
            }
            $newfilterselect = $cleanedfilterselect;
            unset($cleanednewfilterselect);
                            
            $mform->addElement('selectgroups','newfilter','',$newfilterselect, array('class'=>'new_filter_selector filter_selector'));
            $mform->addElement('html','</td><td>');
            $mform->addElement('checkbox','newadvanced','');
            $mform->disabledIf('newadvanced','newfilter', 'eq', 0);
            $mform->addElement('html','</td><td>');
            $mform->addElement('html','</td></tr>');
            $mform->addElement('html','</table></div>');
        } else {
            $mform->addElement('html',"No filters found. Ask your developer to add filter options to the '{$report->source}' source.");
        }

        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','source',$report->source);
        $mform->setType('source', PARAM_TEXT);
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $err = array();
        $err += validate_unique_filters($data);
        return $err;
    }
}


/**
 * Formslib template for edit columns form
 */
class report_builder_edit_columns_form extends moodleform {
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];

        $strmovedown = get_string('movedown','block_lp_reportbuilder');
        $strmoveup = get_string('moveup','block_lp_reportbuilder');
        $strdelete = get_string('delete','block_lp_reportbuilder');
        $strhide = get_string('hide');
        $strshow = get_string('show');
        $spacer = '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" />';

        $mform->addElement('header', 'reportcolumns', get_string('reportcolumns', 'block_lp_reportbuilder'));

        //$mform->setHelpButton('reportcolumns', array('reportbuildercolumns',get_string('columns','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        if(isset($report->columnoptions) && is_array($report->columnoptions) && count($report->columnoptions)>0) {

            $mform->addElement('html','<div>'.get_string('help:columnsdesc','block_lp_reportbuilder').'</div><br />');

            $mform->addElement('html', '<div class="reportbuilderform"><table><tr><th>'.get_string('column','block_lp_reportbuilder').
                '</th><th colspan="2">'.get_string('customiseheading','block_lp_reportbuilder').'</th><th>'.get_string('options','block_lp_reportbuilder').'</th><tr>');
            
            $columnsselect = $report->get_columns_select();
            $columnoptions = array();

            if(isset($report->columns) && is_array($report->columns) && count($report->columns)>0) {
                $columns = $report->columns;
                $colcount = count($columns);
                $i = 1;
                foreach($columns as $index => $column) {
                    $columnoptions["{$column->type}_{$column->value}"] = $column->heading;
                    if(!isset($column->required) || !$column->required) {
                        $row = array();
                        $type = $column->type;
                        $value = $column->value;
                        $field = "{$column->type}-{$column->value}";
                        $heading = $column->heading;
                        $cid = $index;
                        $mform->addElement('html','<tr colid="'.$cid.'"><td>');
                        $mform->addElement('selectgroups',"column{$cid}",'',$columnsselect, array('class' => 'column_selector'));
                        $mform->setDefault("column{$cid}", $field);
                        $mform->addElement('html','</td><td>');

                        $mform->addElement('advcheckbox', "customheading{$cid}", '', '', array('class' => 'column_custom_heading_checkbox', 'group' => 0), array(0,1));
                        $mform->setDefault("customheading{$cid}", $column->customheading);

                        $mform->addElement('html','</td><td>');
                        $mform->addElement('text',"heading{$cid}",'','class="column_heading_text"');
                        $mform->setType("heading{$cid}", PARAM_TEXT);
                        $mform->setDefault("heading{$cid}",$column->heading);
                        $mform->addElement('html','</td><td>');
                        
                        // show/hide link
                        if($column->hidden == 0) {
                            $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?h=1&amp;id='.$id.'&amp;cid='.$cid.'" title="'.$strhide.'" class="hidecolbtn" title="Hide"><img src="'.$CFG->wwwroot.'/pix/t/hide.gif" class="iconsmall" alt="'.$strhide.'" /></a>');
                        } else {
                            $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?h=0&amp;id='.$id.'&amp;cid='.$cid.'" title="'.$strshow.'" class="showcolbtn" title="Show"><img src="'.$CFG->wwwroot.'/pix/t/show.gif" class="iconsmall" alt="'.$strshow.'" /></a>');
                        }
                        // delete link
                        $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?d=1&amp;id='.$id.'&amp;cid='.$cid.'" title="'.$strdelete.'" class="deletecolbtn" title="Delete"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a>');
                        // move up link
                        if($i != 1) {
                            $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?m=up&amp;id='.$id.'&amp;cid='.$cid.'" title="'.$strmoveup.'" class="movecolupbtn" title="Move Up"><img src="'.$CFG->wwwroot.'/pix/t/up.gif" class="iconsmall" alt="'.$strmoveup.'" /></a>');
                        } else {
                            $mform->addElement('html', $spacer);
                        }

                        // move down link
                        if($i != $colcount) {
                            $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/columns.php?m=down&amp;id='.$id.'&amp;cid='.$cid.'" title="'.$strmovedown.'" class="movecoldownbtn" title="Move Down"><img src="'.$CFG->wwwroot.'/pix/t/down.gif" class="iconsmall" alt="'.$strmovedown.'" /></a>');
                        } else {
                            $mform->addElement('html', $spacer);
                        }

                        $mform->addElement('html','</td></tr>');
                        $i++;
                    }
                }
            } else {
                $mform->addElement('html','<p>'.get_string('nocolumnsyet','block_lp_reportbuilder').'</p>');
            }

            $mform->addElement('html','<tr><td>');
            $newcolumnsselect = array_merge(
                array(
                    get_string('new') => array(0 => get_string('addanothercolumn','block_lp_reportbuilder'))
                ),
                $columnsselect);
            
            // Remove already-added cols from the new col selector
            $cleanednewcolselect = $newcolumnsselect;
            foreach ($newcolumnsselect as $okey => $optgroup) {
                foreach ($optgroup as $typeval => $heading) {
                    $typevalarr = explode('-', $typeval);
                    foreach ($report->columns as $curcol) {
                        if ($curcol->type == $typevalarr[0] && $curcol->value == $typevalarr[1]) {
                            unset($cleanednewcolselect[$okey][$typeval]);
                        }
                    }
                }
            }
            $newcolumnsselect = $cleanednewcolselect;
            unset($cleanednewcolselect);
            
            $mform->addElement('selectgroups','newcolumns','',$newcolumnsselect, array('class' => 'column_selector new_column_selector'));
            $mform->addElement('html','</td><td>');
            $mform->addElement('advcheckbox', "newcustomheading", '', '', array('id' => 'id_newcustomheading', 'class' => 'column_custom_heading_checkbox', 'group' => 0), array(0,1));
            $mform->setDefault("newcustomheading", 0);
            $mform->addElement('html','</td><td>');

            $mform->addElement('text','newheading','','class="column_heading_text"');
            $mform->setType('newheading', PARAM_TEXT);
            // do manually as disabledIf doesn't play nicely with using JS to update heading values
            // $mform->disabledIf('newheading','newcolumns', 'eq', 0);
            $mform->addElement('html','</td><td>');
            $mform->addElement('html','</td></tr>');
            $mform->addElement('html','</table></div>');

            // if the report is referencing columns that don't exist in the
            // source, display them here so the user has the option to delete
            // them
            if(count($report->badcolumns)) {
                $mform->addElement('header', 'badcols', get_string('badcolumns', 'block_lp_reportbuilder'));
                $mform->addElement('html','<p>'.get_string('badcolumnsdesc','block_lp_reportbuilder').'</p>');

                $mform->addElement('html',
                    '<div class="reportbuilderform"><table><tr><th>' .
                    get_string('type','block_lp_reportbuilder') . '</th><th>' .
                    get_string('value', 'block_lp_reportbuilder') . '</th><th>' .
                    get_string('heading','block_lp_reportbuilder') . '</th><th>' .
                    get_string('options','block_lp_reportbuilder'). '</th><tr>');
                foreach($report->badcolumns as $bad) {

                    $mform->addElement('html','<tr><td>' . $bad['type'] .
                        '</td><td>' . $bad['value'] .
                        '</td><td>' .$bad['heading'] . '</td><td>' .
                        '<a href="' . $CFG->wwwroot .
                        '/blocks/lp_reportbuilder/columns.php?d=1&amp;id=' . $id .
                        '&amp;cid=' . $bad['id'] . '" title="' . $strdelete .
                        '"><img src="' . $CFG->wwwroot .
                        '/pix/t/delete.gif" class="iconsmall" alt="' . $strdelete
                        . '" /></a></td></tr>');
                }
                $mform->addElement('html','</table></div>');
            }


            $mform->addElement('header','sorting',get_string('sorting','block_lp_reportbuilder'));
            //$mform->setHelpButton('sorting', array('reportbuildersorting',get_string('sorting','block_lp_reportbuilder'),'block_lp_reportbuilder'));

            $pick = array('' => get_string('noneselected','block_lp_reportbuilder'));
            $select = array_merge($pick, $columnoptions);
            $mform->addElement('select','defaultsortcolumn', get_string('defaultsortcolumn','block_lp_reportbuilder'), $select);
            $mform->setDefault('defaultsortcolumn', $report->defaultsortcolumn);


            $radiogroup = array();
            $radiogroup[] =& $mform->createElement('radio', 'defaultsortorder', '', get_string('ascending','block_lp_reportbuilder'), SORT_ASC);
            $radiogroup[] =& $mform->createElement('radio', 'defaultsortorder', '', get_string('descending','block_lp_reportbuilder'), SORT_DESC);
            $mform->addGroup($radiogroup, 'radiogroup', get_string('defaultsortorder','block_lp_reportbuilder'), '<br />', false);
            $mform->setDefault('defaultsortorder', $report->defaultsortorder);
        } else {

                $mform->addElement('html', get_string('error:nocolumns', 'block_lp_reportbuilder', $report->source));
            }

        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','source',$report->source);
        $mform->setType('source', PARAM_TEXT);
        $this->add_action_buttons();
        
        // remove the labels from the form elements
        $renderer =& $mform->defaultRenderer();
        $select_elementtemplate = '<div class="fitem"><div class="felement fselectgroups">{element}</div></div>';
        $check_elementtemplate = '<div class="fitem"><div class="felement fcheckbox">{element}</div></div>';
        $text_elementtemplate = '<div class="fitem"><div class="felement ftext">{element}</div></div>';
        $renderer->setElementTemplate($select_elementtemplate, 'newcolumns' . $index);
        $renderer->setElementTemplate($check_elementtemplate, 'newcustomheading' . $index);
        $renderer->setElementTemplate($text_elementtemplate, 'newheading' . $index);
        foreach ($columns as $index => $unused) {
            $renderer->setElementTemplate($select_elementtemplate, 'column' . $index);
            $renderer->setElementTemplate($check_elementtemplate, 'customheading' . $index);
            $renderer->setElementTemplate($text_elementtemplate, 'heading' . $index);
        }
    }


    function validation($data, $files) {
        $err = array();
        $err += validate_unique_columns($data);
        $err += validate_none_empty_heading_columns($data);
        return $err;
    }


}


/**
 * Formslib template for content restrictions form
 */
class report_builder_edit_content_form extends moodleform {
    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];

        // get array of content options
        $contentoptions = isset($report->contentoptions) ?
            $report->contentoptions : array();

        $mform->addElement('header', 'contentheader', get_string('contentcontrols', 'block_lp_reportbuilder'));

        if(count($contentoptions)) {
            if($report->embeddedurl !== null) {
                $mform->addElement('html','<p>'.get_string('embeddedcontentnotes','block_lp_reportbuilder').'</p>');
            }

            $radiogroup = array();
            $radiogroup[] =& $mform->createElement('radio', 'contentenabled', '', get_string('nocontentrestriction','block_lp_reportbuilder'), 0);
            $radiogroup[] =& $mform->createElement('radio', 'contentenabled', '', get_string('withcontentrestrictionany','block_lp_reportbuilder'), 1);
            $radiogroup[] =& $mform->createElement('radio', 'contentenabled', '', get_string('withcontentrestrictionall','block_lp_reportbuilder'), 2);
            $mform->addGroup($radiogroup, 'radiogroup', get_string('restrictcontent','block_lp_reportbuilder'), '<br />', false);
            //$mform->setHelpButton('radiogroup', array('reportbuildercontentmode',get_string('restrictcontent','block_lp_reportbuilder'),'block_lp_reportbuilder'));
            $mform->setDefault('contentenabled', $DB->get_field('report_builder', 'contentmode', array('id' => $id)));

            // display any content restriction form sections that are enabled for
            // this source
            foreach($contentoptions as $option) {
                $classname = 'rb_' . $option->classname.'_content';
                if(class_exists($classname)) {
                    $obj = new $classname();
                    $obj->form_template($mform, $id, $option->title);
                }
            }

            $mform->addElement('hidden','id',$id);
            $mform->setType('id', PARAM_INT);
            $mform->addElement('hidden','source',$report->source);
            $mform->setType('source', PARAM_TEXT);
            $this->add_action_buttons();
        } else {
            // there are no content restrictions for this source. Inform the user
            $mform->addElement('html',
                get_string('error:nocontentrestrictions',
                'block_lp_reportbuilder', $report->source));
        }
    }
}

/**
 * Formslib template for access restrictions form
 */
class report_builder_edit_access_form extends moodleform {
    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];

        $mform->addElement('header', 'access', get_string('accesscontrols', 'block_lp_reportbuilder'));

        if($report->embeddedurl !== null) {
            $mform->addElement('html','<p>'. get_string('embeddedaccessnotes','block_lp_reportbuilder').'</p>');
        }
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'accessenabled', '', get_string('norestriction','block_lp_reportbuilder'), 0);
        $radiogroup[] =& $mform->createElement('radio', 'accessenabled', '', get_string('withrestriction','block_lp_reportbuilder'), 1);
        $mform->addGroup($radiogroup, 'radiogroup', get_string('restrictaccess','block_lp_reportbuilder'), '<br />', false);
        $mform->setDefault('accessenabled', $DB->get_field('report_builder', 'accessmode', array('id' => $id)));
        //$mform->setHelpButton('radiogroup', array('reportbuilderaccessmode',get_string('restrictaccess','block_lp_reportbuilder'),'block_lp_reportbuilder'));

        // loop round classes, only considering classes that extend rb_base_access
        foreach(get_declared_classes() as $class) {
            if(is_subclass_of($class, 'rb_base_access')) {
                $obj = new $class();
                // add any form elements for this access option
                $obj->form_template($mform, $id);
            }
        }

        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','source',$report->source);
        $mform->setType('source', PARAM_TEXT);
        $this->add_action_buttons();
    }

}


/**
 * Method to check a shortname is unique in database
 *
 * @param array $data Array of data from the form
 *
 * @return array Array of errors to display on failure
 */
function validate_shortname($data) {
	global $DB;
    $errors = array();

    if($foundreports = $DB->get_records('report_builder','shortname',$data['shortname'])) {
        if(!empty($data['id'])) {
            unset($foundreports[$data['id']]);
        }
        if(!empty($foundreports)) {
            $errors['shortname'] = get_string('shortnametaken','block_lp_reportbuilder');
        }
    }
    return $errors;

}

/**
 * Method to check each column is only included once
 *
 * Flexible table breaks if not used as headers must be distinct
 *
 * @param array $data Array of data from the form
 *
 * @return array Array of errors to display on failure
 */
function validate_unique_columns($data) {
	global $DB;
    $errors = array();

    $id = $data['id'];
    $used_cols = array();
    if($currentcols = $DB->get_records('report_builder_columns', array('reportid' => $id))) {
        foreach($currentcols as $col) {
            $field = "column{$col->id}";
            if(isset($data[$field])) {
                if(array_key_exists($data[$field], $used_cols)) {
                    $errors[$field] = get_string('norepeatcols','block_lp_reportbuilder');
                } else {
                    $used_cols[$data[$field]] = 1;
                }
            }
        }
    }

    // also check new column if set
    if(isset($data['newcolumns'])) {
        if(array_key_exists($data['newcolumns'], $used_cols)) {
            $errors['newcolumns'] = get_string('norepeatcols','block_lp_reportbuilder');
        }
    }
    return $errors;
}


/**
 * Method to check column headings aren't empty (or just whitespace)
 *
 * @param array $data Array of data from the form
 *
 * @return array Array of errors to display on failure
 */
function validate_none_empty_heading_columns($data) {
    $errors = array();

    foreach($data as $key => $value) {
        // only look at the heading fields
        if(preg_match('/^heading\d+/', $key)) {
            if(trim($value) == '') {
                $errors[$key] = get_string('noemptycols', 'block_lp_reportbuilder');
            }
        }
    }

    return $errors;
}


/**
 * Method to check each filter is only included once
 *
 * @param array $data Array of data from the form
 *
 * @return array Array of errors to display on failure
 */
function validate_unique_filters($data) {
	global $DB;
    $errors = array();

    $id = $data['id'];
    $used_filters = array();
    if($currentfilters = $DB->get_records('report_builder_filters', array('reportid' => $id))) {
        foreach($currentfilters as $filt) {
            $field = "filter{$filt->id}";
            if(isset($data[$field])) {
                if(array_key_exists($data[$field], $used_filters)) {
                    $errors[$field] = get_string('norepeatfilters','block_lp_reportbuilder');
                } else {
                    $used_filters[$data[$field]] = 1;
                }
            }
        }
    }

    // also check new filter if set
    if(isset($data['newfilter'])) {
        if(array_key_exists($data['newfilter'], $used_filters)) {
            $errors['newfilter'] = get_string('norepeatfilters','block_lp_reportbuilder');
        }
    }
    return $errors;
}


/**
 * Formslib template for saved searches form
 */
class report_builder_save_form extends moodleform {
    function definition() {
        global $CFG,$USER,$SESSION;
        $mform =& $this->_form;
        $report = $this->_customdata['report'];
        $id = $this->_customdata['id'];
        $filterparams = $report->get_restriction_descriptions('filter');
        $shortname = $report->shortname;
        $filtername = 'filtering_'.$shortname;
        $searchsettings = serialize($SESSION->$filtername);
        $params = implode('<br />',$filterparams);

        $mform->addElement('header', 'savesearch', get_string('createasavedsearch', 'block_lp_reportbuilder'));
        $mform->addElement('static', 'description', '', get_string('savedsearchdesc','block_lp_reportbuilder'));
        $mform->addElement('static', 'params', get_string('currentsearchparams','block_lp_reportbuilder'), $params);
        $mform->addElement('text','name', get_string('searchname','block_lp_reportbuilder'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('advcheckbox', 'public', get_string('publicallyavailable','block_lp_reportbuilder'), '', null, array(0,1));
        $mform->addElement('hidden','id',$id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'search', $searchsettings);
        $mform->setType('search', PARAM_TEXT);
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $this->add_action_buttons();
    }
}



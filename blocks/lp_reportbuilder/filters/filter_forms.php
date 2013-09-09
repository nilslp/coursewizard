<?php //$Id$

require_once($CFG->libdir.'/formslib.php');

class add_filter_form extends moodleform {

    function definition() {
        global $SESSION;
        $mform       =& $this->_form;
        $extraparams = $this->_customdata['extraparams'];
        $shortname      = $this->_customdata['shortname'];
        $filtername = 'filtering_'.$shortname;
        
        // If this is 'couse_search', i.e. the old 'Course Overview' report, and the config is enalbed,
        // it should be possible to filter the results by the user's hierarchy.
        // The reason why it can't be done as a filter of the source in the usual way is because hierarchy 
        // is not an attribute of courses, therefore not a column option
        if ($this->_customdata['shortname'] == 'course_search' && get_config('reportbuilder', 'showcoursesearchhierarchyfilter') == 1) {
            $hierarchy_filter_data = new stdClass();
            $hierarchy_filter_data->type = 'course_users';
            $hierarchy_filter_data->value = 'hierarchyid';

            $hierarchy_filter = new filter_hierarchy($hierarchy_filter_data, $filtername, 'org');
            $hierarchy_filter->_filter->label = 'Directorate / Department';
            $hierarchy_filter->_filter->advanced = 0;

            $this->_customdata['fields'][$hierarchy_filter->_name] = $hierarchy_filter;
        }
        
        // If this is 'couse_search', i.e. the old 'Course Overview' report, and the config is enabled,
        // it should be possible to filter the results by the user's deleted status.
        if ($this->_customdata['shortname'] == 'course_search' && get_config('reportbuilder', 'allowdeletedusers') == 1) {
            // Define the filter -- unfortunately since the affected filter is not a column attribute of
            // the 'course' search it will have to be interrogated separately in the join
            $delete_filter_data = new stdClass();
            $delete_filter_data->type = 'user';
            $delete_filter_data->value = 'deleted';

            $deleted_filter_options = array(1 => get_string('yes'), 0 => get_string('no'));

            $deleted_filter = new filter_simpleselect($delete_filter_data, $filtername, $deleted_filter_options); //'includedeleted', 'includedeleted', );

            $deleted_filter->_filter->label = get_string('includedeleted', 'block_lp_reportbuilder');
            $deleted_filter->_filter->advanced = 0;

            $this->_customdata['fields'][$deleted_filter->_name] = $deleted_filter;
        }
        
        /**
         * The below filter is not supported anymore by modules summary, nixing: see issue #6812
         * 
        
        if ($this->_customdata['shortname'] == 'modules_summary') {
            $from_date_filter_data = new stdClass();
            $from_date_filter_data->type = 'section';
            $from_date_filter_data->value = 'statusdate';

            
            //$from_date_filter = new filter_simpleselect($delete_filter_data, $filtername);
            $from_date_filter = new filter_date($from_date_filter_data, $filtername);
            $from_date_filter->_filter->label = get_string('accessdate', 'block_lp_reportbuilder');
            $from_date_filter->_filter->advanced = 0;

            $this->_customdata['fields'][$from_date_filter->_name] = $from_date_filter;
        }
         
         * 
         */
        
        $fields      = $this->_customdata['fields'];

        if($fields && is_array($fields) && count($fields) > 0) {
            $mform->addElement('header', 'newfilterreports', get_string('searchby','block_lp_reportbuilder'));

            foreach($fields as $ft) {
                $ft->setupForm($mform);
            }

            // in case we wasnt to track some page params
            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $mform->addElement('html','<br />');
            $mform->addElement('html','<table align="center"><tr><td align="left">');

            
            // Add button
            $mform->addElement('submit', 'addfilter', get_string('search','block_lp_reportbuilder'));

            $mform->addElement('html','</td><td align="right">');

            // clear form button
            $mform->addElement('submit', 'clearfilter', get_string('clearform','block_lp_reportbuilder'));

            $mform->addElement('html','</td></tr></table>');
        }
        
        $mform->updateAttributes(array('class'=>'mform hide', 'id'=>'report_filter_form'));
    }

    function definition_after_data() {
        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];

        if($fields && is_array($fields) && count($fields) > 0) {

            foreach($fields as $ft) {
                if(method_exists($ft, 'definition_after_data')) {
                    $ft->definition_after_data($mform);
                }
            }
        }
    }
}

/*
 * This form is no longer used as the filter behaves more like 
 * a search form now. Left in in-case someone decides they would
 * prefer a filter interface
 */
class active_filter_form extends moodleform {

    function definition() {
        global $SESSION; // this is very hacky :-(

        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];
        $shortname      = $this->_customdata['shortname'];
        $filtername = 'filtering_'.$shortname;

        if (!empty($SESSION->{$filtername})) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->{$filtername} as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

            $mform->addElement('hidden','shortname',$shortname);
            $mform->setType('shortname', PARAM_TEXT);

            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselected', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeall', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    }
}

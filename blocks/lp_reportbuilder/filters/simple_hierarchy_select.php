<?php

//$Id$

require_once($CFG->dirroot . '/blocks/lp_reportbuilder/filters/lib.php');

/**
 * Description of simpleHierarchySelect
 * Simple selection 
 * @author Francis Byrne
 */
class filter_simple_hierarchy_select extends filter_type {

    /**
     * options for the list values
     */
    var $_options;
    var $_default;

    function filter_simple_hierarchy_select($filter, $sessionname, $type = 'simple_hierarchy_select') {
        parent::filter_type($filter, $sessionname);
        $this->_type = $type;
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function get_operators() {
        return array(1 => get_string('isequalto', 'filters'),
            2 => get_string('isnotequalto', 'filters'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        global $SESSION;
        $sessionname = $this->_sessionname;
        $label = $this->_filter->label;
        $advanced = $this->_filter->advanced;
        $hierarchy = Hierarchy::get_instance();

        $objs = array();
        $objs[] = & $mform->createElement('select', $this->_name . '_op', null, $this->get_operators());
        $grp = & $mform->addElement('group', $this->_name . '_grp', $label, $objs, '', false);

        $sel = & $mform->addElement('hierselect', 'Organisations', 'Choose organisation:');
        $sel->setOptions($hierarchy->get_hierarchy_arrays(array('createdummies' => 1)));

        $mform->disabledIf($this->_name, $this->_name . '_op', 'eq', 0);
        if (!is_null($this->_default)) {
            $mform->setDefault($this->_name, $this->_default);
        }
        if ($advanced) {
            $mform->setAdvanced($this->_name . '_grp');
        }

        // set default values
        if (array_key_exists($this->_name, $SESSION->{$sessionname})) {
            $defaults = $SESSION->{$sessionname}[$this->_name];
        }
        //TODO get rid of need for [0]
        if (isset($defaults[0]['operator'])) {
            $mform->setDefault($this->_name . '_op', $defaults[0]['operator']);
        }
        if (isset($defaults[0]['value'])) {
            $mform->setDefault($this->_name, $defaults[0]['value']);
        }
        
        $mform->setType($this->_name, PARAM_RAW);
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field = "Organisations";
        $operator = "user_hierarchy-hierarchyid_op";
       
        if (array_key_exists($field, $formdata) && $formdata->$field !== '') {

            $test = array('selection' => $formdata->$field);
            $tmp = null;

            // to test for which level Division / Section / Team has been selected and return the id for the selected level
            for ($x = 0; $x < sizeof($test['selection']); $x++) {
                $tmp = $test['selection'][$x];
                if ($tmp == "-1") {
                    if (!($x == 0)) {
                        $tmp = $test['selection'][$x - 1];      //selection is 'all' at this stage so taking the id of the next level up// selection is all for the top level
                        $data = array('operator' => (int) $formdata->$operator,
                            'value' => $tmp);
                        return $data;
                    } else {
                        $tmp = $test['selection'][$x];          // selection is all for the top level                        
                        $data = array('operator' => (int) $formdata->$operator,
                            'value' => $tmp);
                        return $data;
                    }
                } else if ($x == (sizeof($test['selection'])-1)){
                    $tmp = $test['selection'][$x];          // selection is all for the top level                        
                    $data = array('operator' => (int) $formdata->$operator,
                        'value' => $tmp);
                    return $data;
                }
            }
            return false;
        }
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return string the filtering condition or null if the filter is disabled
     */
//    function get_sql_filter($data) {
//        global $DB;
//        $operator = $data['operator'];
//        $recursive = (isset($data['recursive']) && $data['recursive']);
//        $value = $data['value'];
//        $query = $this->_filter->type . '.' . $this->_filter->value;
//
//        switch ($operator) {
//            case 1:
//                $token = ' = ';
//                break;
//            case 2:
//                $token = ' <> ';
//                break;
//            default:
//                // return 1=1 instead of TRUE for MSSQL support
//                return ' 1=1 ';
//        }
//
//        $path = $value;
//
//        if ($recursive) {
//            $sub = $DB->get_records_sql("SELECT id FROM {lp_hierarchy} WHERE path LIKE '%/$path/%'");
//            if (count($sub)) {
//                $sub = array_keys($sub);
//                $sub [] = $path;
//                $path = implode(',', $sub);
//            }
//        }
//
//        $path = " ( $path ) ";
//
//        if ($operator == 2) {
//            // check for null case for is not operator
//            return '(' . $query . $token . $path . " OR " . $query . ' IS NULL)';
//        } else {
//            return $query . $token . $path;
//        }
//        return '';
//    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        $operators = $this->get_operators();
        $operator  = $data['operator'];
        $value     = $data['value'];
        $label = $this->_filter->label;

        if (empty($operator)) {
            return '';
        }

        $a = new object();
        $a->label    = $label;
        $a->value    = '"'.s($this->_options[$value]).'"';
        $a->operator = $operators[$operator];

        return get_string('selectlabel', 'filters', $a);
    }
    
    function get_sql_filter($data) {
        global $DB;
        
        if ($data['value'] == -1 || $data['value'] == 0) {
            return "(1=1)";
        }
        
        $operator = $data['operator'];
        $value    = (int)addslashes($data['value']);
        $query    = $this->_filter->get_field();
        $alias    = $this->_filter->type;
        
        if (!is_numeric($value)) {
            $value = 0;
        }
        
        $include = $DB->get_records_sql("SELECT DISTINCT(id) FROM {lp_hierarchy} WHERE path LIKE '%/{$value}/%' OR path LIKE '%/{$value}' ");

        if (empty($include)) {
            $include = '-1';
        } else {
            $include = implode(',',array_keys($include));
        }

        switch($operator) {
            case 1:
                $token = ' IN ';
                break;
            case 2:
                $token = ' NOT IN ';
                break;
            default:
                // return 1=1 instead of TRUE for MSSQL support
                return ' 1=1 ';
        }

        return '(' . $alias . '.' . $query . $token  .'('. $include . '))';
    }
}


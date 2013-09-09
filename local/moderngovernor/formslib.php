<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('lib.php');

class moderngovernorsettingsform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('settings', 'local_moderngovernor'));

        $mform->addElement('checkbox', 'synchierarchy', get_string('synchierarchy', 'local_moderngovernor'));
        $mform->addHelpButton('synchierarchy', 'synchierarchy', 'local_moderngovernor');
        
        $mform->addElement('checkbox', 'enabledemousers', get_string('enabledemousers', 'local_moderngovernor'));
        $mform->addHelpButton('enabledemousers', 'enabledemousers', 'local_moderngovernor');
        
        $mform->addElement('text', 'instancename', get_string('instancename', 'local_moderngovernor'));
        $mform->setType('instancename', PARAM_TEXT);
        
        $mform->addHelpButton('instancename', 'instancename', 'local_moderngovernor');

        $this->add_action_buttons();
    }

    function process() {
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }

        if (isset($data->synchierarchy)) {
            set_config('synchierarchy', (int)$data->synchierarchy, 'local_moderngovernor');
        } else {
            set_config('synchierarchy', 0, 'local_moderngovernor');            
        }        
        
        if (isset($data->enabledemousers)) {
            set_config('enabledemousers', (int)$data->enabledemousers, 'local_moderngovernor');
        } else {
            set_config('enabledemousers', 0, 'local_moderngovernor');            
        }
        
        if (isset($data->instancename)) {
            set_config('instancename', $data->instancename, 'local_moderngovernor');
        }
        
        return true;
    }

}

class moderngovernorschoolsearchform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;
        $mform->updateAttributes(array('id'=>'school-search-form'));

        $mform->addElement('header', 'searchbox', get_string('search'));

        $mform->addElement('text', 'search', get_string('searchbyschool', 'local_moderngovernor'));
        $mform->setType('search', PARAM_TEXT);
        $mform->addHelpButton('search', 'searchbyschool', 'local_moderngovernor');
        
        $leaoptions = get_moderngovernor_leas_for_select();        
        $mform->addElement('select', 'lea', get_string('leaselect', 'local_moderngovernor'), $leaoptions);
        $mform->addHelpButton('lea', 'leaselect', 'local_moderngovernor');
        
        $statusoptions = array(
            ' -- '.get_string('any').' -- ',
            get_string('disabled','local_moderngovernor'),
            get_string('enabled','local_moderngovernor')
        );
        $mform->addElement('select', 'status', get_string('statusselect', 'local_moderngovernor'), $statusoptions);
        $mform->addHelpButton('status', 'statusselect', 'local_moderngovernor');
        
        $mform->addElement('button', 'combine', get_string('combine', 'local_moderngovernor'));
        //$mform->addHelpButton('combine', 'combine', 'local_moderngovernor');
    }
}

class moderngovernorschooladminform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;
        $mform->updateAttributes(array('id'=>'school-admin-form'));

       // $mform->addElement('header', 'combineschools', get_string('combineschools','local_moderngovernor'));

        $mform->addElement('html', '<div id="combine-list">' .  get_string('noschoolsselected','local_moderngovernor') . '</div>');
        
        $mform->addElement('text', 'newname', get_string('newname', 'local_moderngovernor'));
        $mform->setType('newname', PARAM_TEXT);
        // $mform->addHelpButton('newname', 'newname', 'local_moderngovernor');
        $mform->addRule('newname', null, 'required', null, 'server');
        
        $leaoptions = get_moderngovernor_leas_for_select();        
        $mform->addElement('select', 'newlea', get_string('newleaselect', 'local_moderngovernor'), $leaoptions);
        // $mform->addHelpButton('newlea', 'newleaselect', 'local_moderngovernor');
        $mform->addRule('newlea', null, 'required', null, 'server');

    }

}

class moderngovernorusersearchform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;
        $mform->updateAttributes(array('id'=>'user-search-form'));

        $mform->addElement('header', 'searchbox', get_string('search'));

        $mform->addElement('text', 'search', get_string('searchbyuser', 'local_moderngovernor'));
        $mform->setType('search', PARAM_TEXT);
        $mform->addHelpButton('search', 'searchbyuser', 'local_moderngovernor');   
        
        $statusoptions = array(
            ' -- '.get_string('any').' -- ',
            get_string('unconfirmed','local_moderngovernor'),
            get_string('confirmed','local_moderngovernor')
        );
        $mform->addElement('select', 'status', get_string('userstatusselect', 'local_moderngovernor'), $statusoptions);
        $mform->addHelpButton('status', 'userstatusselect', 'local_moderngovernor');
    }
}

class moderngovernordemouserform extends moodleform {

    function definition() {
        global $CFG;

        $strrequired = get_string('required');
        
        $mform = &$this->_form;
        $mform->updateAttributes(array('id'=>'account-add-form'));

        $mform->addElement('header', 'userdetails', get_string('userdetails', 'local_moderngovernor'));

        $mform->addElement('text', 'username', get_string('username'));
        $mform->addRule('username', $strrequired, 'required', null, 'client');
        
        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->addRule('firstname', $strrequired, 'required', null, 'client');
        
        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->addRule('lastname', $strrequired, 'required', null, 'client');
        
        $mform->addElement('text', 'email', get_string('email'));
        $mform->addRule('email', $strrequired, 'required', null, 'client');
      //  $mform->addRule('email', null, null, null, 'server');
        
        $this->add_action_buttons(true, get_string('add'));
    }
    
    function validation($data, $files) {
        global $DB,$CFG;
        require_once($CFG->libdir.'/weblib.php');        
        
        $errors= array();
        $mg_cfg = get_moderngovernor_config();
        $lealist = get_moderngovernor_lea_insql(); 
        $level1 = $mg_cfg->map['level1'];
        $level2 = $mg_cfg->map['level2'];
        
        if (empty($data['email']) || !validate_email($data['email'])){
            $errors['email'] = get_string('error:noemailspecified', 'local_moderngovernor');
        } else {
            // check we are not duplicating an account!
            if ($rec = $DB->get_record_sql("SELECT gu.id,gu.username,gu.email,gu.firstname,gu.lastname,level1.fullname AS lea, level2.fullname AS school 
                                                FROM `{$mg_cfg->db}`.`{$mg_cfg->table}` gu
                                                JOIN {lp_hierarchy} level1 
                                                ON gu.`$level1` = level1.id
                                                JOIN {lp_hierarchy} level2
                                                ON gu.`$level2` = level2.id
                                                WHERE gu.lea_id IN ($lealist) AND gu.email = ?", array($data['email']))) {                                                    
                $errors['email'] = get_string('error:duplicateaccount', 'local_moderngovernor', $rec);
            }
        }
        return $errors;
    }
    
    function process() {
        global $DB, $CFG, $OUTPUT;
        
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        $mg_cfg = get_moderngovernor_config();
        $lealist = get_moderngovernor_lea_insql(); 
        $level1 = $mg_cfg->map['level1'];
        $level2 = $mg_cfg->map['level2'];
        
        try {
            $depths = $DB->get_records_sql('SELECT level,id FROM {lp_hierarchy_depth}'); 
            $demo = $DB->get_record_sql("SELECT l.id AS leaid,
                                            level1.id AS lea,
                                            level2.id AS school
                                        FROM `{$mg_cfg->db}`.`{$mg_cfg->leatable}` l 
                                        INNER JOIN {lp_hierarchy} level1 
                                            ON level1.id=l.lngLevel AND level1.depthid=? 
                                        INNER JOIN {lp_hierarchy} level2 
                                            ON level2.parentid=level1.id AND level2.depthid=? 
                                        WHERE l.name= ? AND l.instance_id = ?", 
                                        array($depths['1']->id, $depths['2']->id, 'Demo', $mg_cfg->instanceid));
            if (!$demo) {
                throw new Exception(get_string("error:demoleanotfound", 'local_moderngovernor'));
            }
        } catch (Exception $e){
            echo $OUTPUT->notification($e->getMessage(), 'notifyfailure');
            return false;
        }
        
        // create demo account
        $insert = "INSERT INTO `{$mg_cfg->db}`.`{$mg_cfg->table}` (username,password,firstname,lastname,email,date_added,lea_id,school_id,lngLevel,confirmed,rand_key,last_ip,last_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = array(
            $data->username,
            md5('welcome'),
            $data->firstname,
            $data->lastname,
            $data->email,
            time(),
            $demo->leaid,
            $demo->school,
            $demo->lea,
            1,
            '',
            0,
            0
        );
        
        return $DB->execute($insert, $params);
    }
}   
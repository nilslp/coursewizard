<?php

require_once($CFG->dirroot.'/lib/adminlib.php');

class admin_setting_startgroup extends admin_setting {

    public function __construct($name,$group_name=false) {
        $this->nosave = true;
        $this->group_name = $group_name;
        parent::__construct($name, '', '', '');
    }

    public function output_html($data, $query='') {
        $html = '<div class="admin-setting-group '.$this->group_name.'">';
        return $html;
    }
    
    // These are no supposed to return anything
    public function get_setting() { return true; }
    public function write_setting($data) { return ''; }
    
}

class admin_setting_endgroup extends admin_setting {

    public function __construct($name) {
        $this->nosave = true;
        parent::__construct($name, '', '', '');
    }

    public function output_html($data, $query='') {
        $html = '</div>';
        return $html;
    }
    
    // These are no supposed to return anything
    public function get_setting() { return true; }
    public function write_setting($data) { return ''; }
    
}
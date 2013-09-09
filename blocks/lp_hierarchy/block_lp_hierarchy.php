<?php
class block_lp_hierarchy extends block_base {
    function init() {
        $this->title   = get_string('pluginname', 'block_lp_hierarchy');
        $this->version = 2011072904;
    }

    function get_content() {
        // Don't display anything -- there is no UI for this block
        $this->content =  new stdClass;

        $this->content->text = '';
            
        return $this->content;
    }

    function instance_allow_config() {
        return false;
    }
    
    function has_config() {
        return true;
    }
}
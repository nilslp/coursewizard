<?php global $CFG;
  
require_once($CFG->dirroot.'/blocks/lp_course_progress/lib.php');

class block_lp_course_progress extends block_base {
    function init() {
        $this->title = get_string('blocktitle', 'block_lp_course_progress');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => true);
    }

    function get_content () {
      global $CFG,$USER,$PAGE;
      
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        //setting up content output
        $this->content =  new stdClass;      
        
        $renderer = $PAGE->get_renderer('block_lp_course_progress');
        
        $this->content->text = $renderer->block_lp_course_progress_ui();
        
        return $this->content;
    }
    
    function instance_allow_multiple() {
        // Only one instance of this block can exist
        return false;
    }
    
    public function cron() {
        block_lp_course_progress_cron();
    }
    
    /**
     * Does the plugin have a settings page?
     * @return boolean
     */
    function has_config() {
        return true;
    }
}



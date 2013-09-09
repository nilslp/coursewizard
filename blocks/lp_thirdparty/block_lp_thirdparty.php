<?php

class block_lp_thirdparty extends block_base {
	
	function init() {
        $this->title = get_string('blocktitle', 'block_lp_thirdparty');
    }
	
	function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        
        $context = get_context_instance(CONTEXT_SYSTEM);
        
        if (has_capability('block/lp_thirdparty:view', $context)) {
            $this->content->text   = '';
        }
        else {
            $this->content->text = '';
        }
        
        //$this->content->footer = 'Footer here...';

        return $this->content;            
    }
	
	function instance_allow_multiple() {
        // Only one instance of this block can exist
        return false;
    }
	
	/**
    * Standard cron function
    */
    function cron() {
        $this->trace('block_lp_thirdparty_cron() started at '. date('H:i:s'));

        try {
            $this->process();
        } 
        catch (Exception $e) {
            $this->trace('block_lp_thirdparty_cron() failed with an exception:');
            $this->trace($e->getMessage());
        }

        $this->trace('block_lp_thirdparty_cron() finished at ' . date('H:i:s'));
    }
	
	/**
	 * Cron logic
	 */
	function process(){
		global $DB;
		if(isset($this->config->pull_old) && $this->config->pull_old == 1){
			//@todo convert all old users with 'utp_reg' auth to 'thirdparty' auth
			$this->trace('JOB: Convert legacy user\'s to new authentication');
			
		}  
		
	}
	
	/**
    * Helper function for messages
    * @param type $msg The string to output
    */
    function trace($msg) {
        mtrace('lp_thirdparty: ' . $msg);    
    }
	
}

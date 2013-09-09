<?php

class profile_field_hierarchytext extends profile_field_base {

    /**
     * Overwrite the base class to display the data for this field
     */
    function display_data() {
        /// Default formatting
        $data = parent::display_data();

        return $data;
    }

    function edit_field_add($mform) {
    	global $CFG,$PAGE;
    	
        $user_can_edit = $CFG->block_lp_hierarchy_allow_user_edit;
        
        if ($CFG->block_lp_hierarchy_allow_freetext) {
            
            $hierarchy = Hierarchy::get_instance();

            $size = $this->field->param1;
            $maxlength = 1024;  // This is the maxlength as defined in the table

            // Get the label text
            $label_text = format_string($hierarchy->get_max_depth_text());

            /// Create the form field
            $mform->addElement('text', $this->inputname, $label_text, 'maxlength="'.$maxlength.'" size="'.$size.'" ');
            $mform->setType($this->inputname, PARAM_MULTILANG);

            $validation_message = sprintf("%s is required", $label_text);

            $systemcontext = get_context_instance(CONTEXT_SYSTEM);
            $valid_to_edit = (has_capability('moodle/user:create', $systemcontext) || has_capability('moodle/user:update', $systemcontext));
            
            // Allow the user to change the control, so add the required validation
            if ($user_can_edit || $valid_to_edit) {
                $mform->addRule($this->inputname, $validation_message, 'required', null, 'client', true);
           
                $mform->addRule($this->inputname, $validation_message, 'required', null, 'server', true);     
            }
            
            // Callback function for checking that a full hierarchy has been selected
            function check_hierarchy_freetext($value) {
            	if (ltrim(rtrim($value)) == "") {
                    return false;
            	}
            	else {
                    return true;
            	}
            }
                                
            if ($user_can_edit || $valid_to_edit) {
                $mform->addRule($this->inputname, $validation_message, 'callback', 'check_hierarchy_freetext');
            }
            else {
                //$mform->hardFreeze($this->inputname);
            }
            
            /*
            if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
                // Disable the element
                $mform->hardFreeze($this->inputname);
            }
            */
        }
        if($CFG->block_lp_hierarchy_buyways){
            $jsmodule = array(
                    'name'     => 'block_buyways',
                    'fullpath' => '/blocks/lp_hierarchy/javascript/buyways.js',
                    'requires' => array('io', 'node', 'json','querystring-stringify-simple'),
                    'strings' => array(
                        array('buyways_checking', 'block_lp_hierarchy'), //wrong path to image
                        array('buyways_checking_error', 'block_lp_hierarchy'),
                        array('buyways_checking_fail', 'block_lp_hierarchy')
                    )
                );
            $PAGE->requires->js_init_call('M.block_buyways.init', null, true, $jsmodule);

            $hierarchy = Hierarchy::get_instance();

            $size = 4;
            $maxlength = 4;  // This is the maxlength as defined in the table

            // Get the label text
            $label_text = format_string($hierarchy->get_max_depth_text());

            /// Create the form field
            $mform->addElement('text', $this->inputname, $label_text, 'maxlength="'.$maxlength.'" size="'.$size.'" ');
            $mform->setType($this->inputname, PARAM_INTEGER);
            
            $mform->addRule($this->inputname, 'required', 'required', '', 'client');
            
            $mform->addRule($this->inputname, 'min length', 'minlength', 4, 'client');
            $mform->addRule($this->inputname, 'max length', 'maxlength', 4, 'client');
            
            $mform->registerRule('buyways',null,'QuickForm_Buyways',"{$CFG->dirroot}/blocks/lp_hierarchy/qf_rules.php");
            $mform->addRule($this->inputname, get_string('buyways_checking_error_prob', 'block_lp_hierarchy'), 'buyways', '', 'client');
            
            // Callback function for checking that a full hierarchy has been selected
            function check_hierarchy_buyways($value) {
            	if (ltrim(rtrim($value)) == "") {
                    return false;
                }
            	else {
                    return true;
                }
            }

            if ($user_can_edit || has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
                //$mform->addRule($this->inputname, $validation_message, 'callback', 'check_hierarchy_buyways');
            }
            else {
                //$mform->hardFreeze($this->inputname);
            }

        }
    }
}



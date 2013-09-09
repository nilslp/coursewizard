<?php
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

class profile_field_thirdpartyhierarchy extends profile_field_base {
    var $options;
    var $datakey;

    /**
     * Constructor method.
     * Pulls out the options for the menu from the database and sets the
     * the corresponding key for the data if it exists
     */
    function __construct($fieldid=0, $userid=0) {
    	global $CFG;
        //first call parent constructor
        $this->profile_field_base($fieldid, $userid);

        $hierarchy = Hierarchy::get_instance();
        
		$hier_param = array();
		
		$hier_param['append']=array('not'=>get_string('notinlist','auth_thirdparty'));
		
		
		$rdirs = get_config('auth/thirdparty','restrictdirectorates');
		if(!is_array($rdirs)){
	    	$rdirs = unserialize($rdirs);
	    }
		if(count($rdirs)>0){
			$hier_param['restrictparents']=$rdirs;			
		}
		
		//quickly knock out the hierarchy block restriction
		$tmp_config = $CFG->block_lp_hierarchy_restrict_from_signup_list;
		
		$CFG->block_lp_hierarchy_restrict_from_signup_list = '';
		
        $this->options = $hierarchy->get_hierarchy_arrays($hier_param);
		
		$CFG->block_lp_hierarchy_restrict_from_signup_list=$tmp_config ;
		
        /// Set the data key
        if ($this->data !== NULL && $this->data !== '') {
            // Extract the data
            // TODO Maybe check that the value is in the $this->options array?
            $this->datakey = explode(',', $this->data);        	
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param   object   moodleform instance
     */
    function edit_field_add(&$mform) {
    	global $CFG;
    	
		//add instructions
		 $mform->addElement('static','info','',get_string('user_provide_hier_help','auth_thirdparty'));
			
		
    	$hierarchy = Hierarchy::get_instance();
     
    	if ($hierarchy->is_freetext()) {
            $label = $hierarchy->get_hierarchy_field_label_text(true);
    	}
    	else {
            $label = $this->field->name;
    	}
        
        // Create the Element        
        $sel =& $mform->addElement('hierselect', $this->inputname, format_string($label));
	        
        // And add the selection options
        $sel->setOptions($this->options);
        
        // Restriction removed in order for Sign Up page
        // Use the configurable 'locked' property instead to prevent changes after set
        // Allow the user to change the control, so add the required validation
       // $mform->addRule($this->inputname, get_string('select_hierarchy', 'block_lp_hierarchy', $label), 'required', null, 'server', true);     
        $mform->addRule($this->inputname, get_string('select_hierarchy', 'block_lp_hierarchy', $label), 'required', null, 'client', true);

        // Callback function for checking that a full hierarchy has been selected
        function check_hierarchy($values) {
            // TODO Check/fix for 1-level DLE
            $flag = true;

            foreach($values as $value) {
                if ((int)$value === 0 && $value !== 'not') {
                    $flag = false;
                    break;
                }
            }

            return $flag;
        }

        $mform->addRule($this->inputname, get_string('select_hierarchy', 'block_lp_hierarchy', $label), 'callback', 'check_hierarchy');
		
		
		include_once('rule/RequiredEnabled.php');
				
		$mform->addElement('text', $this->inputname . '_extra', get_string('organisation_name', 'auth_thirdparty'));
		$mform->disabledIf($this->inputname . '_extra',$this->inputname . '[' . (count($this->options)-1) . ']', 'neq','not');
		$mform->addRule($this->inputname . '_extra', get_string('select_hierarchy_extra', 'auth_thirdparty', $label), 'requiredenabled', null, 'client', true);
		
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method
     */
    function edit_field_set_default(&$mform) {
        // Not used
    }

    /**
     * The data from the form returns the key. This should be converted to the
     * respective option string to be saved in database
     * Overwrites base class accessor method
     * @param   integer   the key returned from the select input in the form
     */
    function edit_save_data_preprocess($key) {
    	$save_data = '';

    	foreach ($key as $item) {
            $save_data .= $item . ',';
    	}
    	
    	if (strlen($save_data) > 0) {
            // Remove the trailing comma
            $save_data = rtrim($save_data, ',');
    	}
    	else {
            $save_data = NULL;
    	}

        return $save_data;
    }

    /**
     * override to handle buyways
     * @param mixed $usernew 
     */
    public function edit_save_data($usernew) {
        global $CFG,$DB;
         if($CFG->block_lp_hierarchy_buyways){
            if (isset($usernew->{$this->inputname})) {
                if(isset($usernew->profile_field_hierarchytext)){
                    
                    if(($fv = $DB->get_field('lp_hierarchy','oldid',array('id'=>end($usernew->{$this->inputname}))))!==false){
                        
                        if(($fv = $DB->get_field('lp_hierarchy','id', array('oldid'=> $fv . $usernew->profile_field_hierarchytext))) !== false){
                             $usernew->{$this->inputname}[]=$fv;
                        }else{
                            debug('Could not retrieve the id for the hierarchytext', DEBUG_DEVELOPER);
                        }
                        
                    }else{
                        debug('Could not retrieve the old id for the hierarchy', DEBUG_DEVELOPER);
                    }
                                                        
                }else{
                    debug('Hierarchy text field has changed name', DEBUG_DEVELOPER);
                }
            }
         }
        
        parent::edit_save_data($usernew);
    }

    /**
     * When passing the user object to the form class for the edit profile page
     * we should load the key for the saved data
     * Overwrites the base class method
     * @param   object   user object
     */
    function edit_load_user_data(&$user) {
    	$user->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_locked(&$mform) {       
        global $CFG;
        
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        
        if ($this->is_locked() && !(has_capability('moodle/user:create', $systemcontext) or has_capability('moodle/user:update', $systemcontext))) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->datakey);
        }
    }
    
    function display_data() {
    	$hierarchy = Hierarchy::get_instance();
    	
        /// Default formatting
        $data = parent::display_data();

        $data = $hierarchy->convert_user_profile_value_to_hierarchy($data);

        return $data;
    } 
}
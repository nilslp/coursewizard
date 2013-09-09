<?php

class profile_define_hierarchy extends profile_define_base {

    function define_form_specific(&$form) {
        
        $form->addElement('hidden', 'defaultdata', '', 'size="50"');
        $form->setType('defaultdata', PARAM_MULTILANG);
                
    	$hierarchy = Hierarchy::get_instance();
    	
        /// Sample
        $sel =& $form->addElement('hierselect', 'userhierarchy', format_string('Sample'));
        $sel->setOptions($hierarchy->get_hierarchy_arrays());
    }

    function define_validate_specific($data, $files) {
        $err = array();

        return $err;
    }

/*
    function define_save_preprocess($data) {
        $data->param1 = str_replace("\r", '', $data->param1);

        return $data;
    }
*/
}



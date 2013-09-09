<?php

class profile_define_hierarchytext extends profile_define_base {

    function define_form_specific(&$form) {
    	global $CFG;
    	
    	if ($CFG->block_lp_hierarchy_allow_freetext) {
            /// Default data
            $form->addElement('text', 'defaultdata', '', 'size="50"');
            $form->setType('defaultdata', PARAM_MULTILANG);

            /// Param 1 for text type is the size of the field
            $form->addElement('text', 'param1', get_string('profilefieldsize', 'admin'), 'size="6"');
            $form->setDefault('param1', 30);
            $form->setType('param1', PARAM_INT);
    	}
    }
}



<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class depth_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'level', get_string('depthlevel', 'block_lp_hierarchy'));
        $mform->hardFreeze('level');

        $mform->addElement('text', 'fullname', get_string('fullnamedepth', 'block_lp_hierarchy'), 'maxlength="1024" size="50"');
        $mform->addRule('fullname', get_string('missingfullnamedepth', 'block_lp_hierarchy'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnamedepth', 'block_lp_hierarchy'), 'maxlength="100" size="20"');
        $mform->addRule('shortname', get_string('missingshortnamedepth', 'block_lp_hierarchy'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setType('description', PARAM_CLEAN);

        $this->add_action_buttons();
    }
}
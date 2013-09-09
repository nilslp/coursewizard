<?php
class block_lp_hierarchy_edit_form extends block_edit_form {
	protected function specific_definition($mform) {
		// Section header title according to language file.
		$mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));


		$options = array (null=>'Please choose',
		1 => '1',
		2 => '2',
		3 => '3',
		4 => '4');

		// A sample string variable with a default value.
		$mform->addElement('select', 'nameofselectelement', get_string('label_hierarchy_depth', 'block_lp_hierarchy'), $options);

		$mform->addElement('advcheckbox', 'test1', get_string('label_use_freetext', 'block_lp_hierarchy'), null, array('group' => 1));

		//$mform->addElement('text', 'config_text', get_string('blockstring', 'block_simplehtml'));
		//$mform->setDefault('config_text', 'default value');
		//$mform->setType('config_text', PARAM_MULTILANG);
	}
}
<?php
function xmldb_block_lp_thirdparty_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $result = true;
	
	$dbman = $DB->get_manager();
	
    if ($oldversion < 2012090300) {

        // Define field st_freetext to be added to lp_work_queue
        $table = new xmldb_table('lp_work_queue');
        $field = new xmldb_field('st_freetext', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, 'req_attention');

        // Conditionally launch add field st_freetext
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // lp_thirdparty savepoint reached
        upgrade_block_savepoint(true, 2012091400, 'lp_thirdparty');
    }
	
	if ($oldversion < 2012092000) {

        // Define table lp_email_linker to be created
        $table = new xmldb_table('lp_email_linker');

        // Adding fields to table lp_email_linker
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('hierarchyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('domains', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table lp_email_linker
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for lp_email_linker
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // lp_thirdparty savepoint reached
        upgrade_block_savepoint(true, 2012092000, 'lp_thirdparty');
    }
	
	return $result;
}
<?php

function xmldb_local_lp_norfolk_imports_install() {
    global $DB;
    //disable by default
    set_config('check_excel_user_import', 'false', 'lpscheduler');
    $dbman = $DB->get_manager();

       
    // Define table lp_norfolk_data to be created
    $table = new xmldb_table('lp_norfolk_data');

    // Adding fields to table lp_norfolk_data
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('username', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);
    $table->add_field('email', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('hierarchy_level_1', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('hierarchy_level_2', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('firstname', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('lastname', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('existing_user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
    $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

    // Adding keys to table lp_norfolk_data
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    // Conditionally launch create table for lp_norfolk_data
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define table lp_norfolk_lookup to be created
    $table = new xmldb_table('lp_norfolk_lookup');

    // Adding fields to table lp_norfolk_lookup
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('hierarchy_level_1', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('hierarchy_level_2', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
    $table->add_field('hierarchyid', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

    // Adding keys to table lp_norfolk_lookup
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    // Conditionally launch create table for lp_norfolk_lookup
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

}
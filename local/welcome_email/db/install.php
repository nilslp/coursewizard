<?php

/**
 * This script ensures that the lp_welcomeemail table exists - since this table was previously
 * associated with a deprecated plugin and we don't want it to conflict with a previous install
 * 
 * @global type $CFG
 * @global type $DB
 * @global type $OUTPUT
 * @return type 
 */
function xmldb_local_welcome_email_install() {
    global $CFG, $DB, $OUTPUT;
    
    require_once($CFG->dirroot.'/local/welcome_email/lib.php');
    
    local_welcome_email_assert_table_exists();
    welcome_email_legacy_migrate_configs();

    return true;
}
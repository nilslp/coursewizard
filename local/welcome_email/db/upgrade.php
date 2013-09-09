<?php

function xmldb_local_welcome_email_upgrade($oldversion = 0) {    
    global $DB,$OUTPUT;
    
    $dbman = $DB->get_manager();

    if($oldversion < 2013012303){
        // add timesent field
        $table = new xmldb_table('lp_welcomeemail');
        $field = new xmldb_field('timesent', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // update sent items to transfer timesent
        $DB->execute("UPDATE {lp_welcomeemail} SET timesent=timemodified WHERE email_sent=1");
    }
    return true; 
}
  

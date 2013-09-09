<?php

function xmldb_local_dlelegacytools_upgrade($oldversion = 0) {    
    global $DB,$OUTPUT;
    
    $dbman = $DB->get_manager();

    if($oldversion < 2012042400){

        $success = set_config('loginredirect', 'on','local/dlelegacytools');

        if($success){
                echo $OUTPUT->notification('Adding loginredirect', 'notifysuccess');

        }else{
                echo $OUTPUT->notification('Failed to add loginredirect');
        }

        $success=set_config('loginredirect_target', './index.php','local/dlelegacytools');
        
        if($success){
                echo $OUTPUT->notification('Adding loginredirect target', 'notifysuccess');
                upgrade_plugin_savepoint(true, 2012042400,'local','dlelegacytools');

        }else{
                echo $OUTPUT->notification('Failed to add loginredirect target');
        }
    }
    
    if ($oldversion < 2012042600) {
        
        mtrace( "adding welcome email settings<br>.");
        
        $welcome_email_items = new stdClass();
        //$welcome_emai_items->welcomeemail_enabled = 'true'; use blockwelcomemeail setting in lpscheduler config item.
        $welcome_email_items->schedulewelcomeemail = 0;
        $welcome_email_items->enable_email_template = 'true';               
        $welcome_email_items->new_user_cc = '';
        $welcome_email_items->new_user_subject = '';		
        $welcome_email_items->new_user_email_body = '';
        $welcome_email_items->new_user_email_from = '';
        $welcome_email_items->self_reg_email_cc = '';
        $welcome_email_items->self_reg_welcome_link = 'true';
        $welcome_email_items->self_reg_confirmation_text = htmlentities('Your new account on <sitename> must be activated first. To activate your account simply click on the following link:');
        $welcome_email_items->self_reg_email_body = htmlentities("Enter customised email here. If 'self_reg_welcome_link' is 'on' then the content inside 'self_reg_confirmation_text' will be added before this content.");
       
        $welcome_email = new stdClass();
        
        foreach( $welcome_email_items as $key=>$value ){
         
            $welcome_email->plugin = 'lpscheduler_welcomeemail';
            $welcome_email->name = $key;
            $welcome_email->value = $value;            
            
            $exists = $DB->get_field('config_plugins','id',array('plugin'=>$welcome_email->plugin,'name'=>$welcome_email->name));
            
            if(!$exists){                  
                $DB->insert_record('config_plugins',$welcome_email,false);	
            }
        }
        upgrade_plugin_savepoint(true, 2012042600,'local','dlelegacytools');
    }
    
    
    // 2.+ welcomeemail table

    if ($oldversion < 2012051000) {
          
        $table = $dbman->table_exists('lp_welcomeemail');

        if(!$table) {

            // create new table to store welcome email sent settings.
            $table = new xmldb_table('lp_welcomeemail');

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,null);
            $table->add_field('email_sent',XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'1');
            $table->add_field('timemodified',XMLDB_TYPE_INTEGER, '10', null, null, null,'0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 

            $table->add_index('userid',  XMLDB_INDEX_UNIQUE, array('userid'));

            $dbman->create_table($table);  

        }

        if( $table ){

            // for dbs that have the welcomeemail field grab the value of this field and populate the new table
            // created.
            $welcome_field = $dbman->field_exists('user', 'welcomeemail');

            if( $welcome_field ){

                $users =  $DB->get_records_sql('SELECT ID, welcomeemail FROM {user}');

                if( !empty( $users) ){

                    foreach( $users as $rec ){

                        $exists = $DB->record_exists('lp_welcomeemail', array( 'userid'=>$rec->id ) ); 

                        if(!$exists){

                            $record = new stdClass();
                            $record->userid = $rec->id;
                            $record->email_sent = $rec->welcomeemail;
                            $record->timemodified = '0';

                            $DB->insert_record('lp_welcomeemail', $record, true ); 

                            echo $OUTPUT->notification('added new user record to lp_welcomeemail', 'notifysuccess');                         

                        }
                    }
                    upgrade_plugin_savepoint(true, 2012051000,'local','dlelegacytools');

                }else{
                    echo $OUTPUT->notification('Failed to add new users to lp_welcomeemail');
                }

            }
        }
          
    }

 if ($oldversion < 2012051500) {
    
     $table = $dbman->table_exists('lp_welcomeemail');
     
     if($table) {
         
         $field = $dbman->field_exists('lp_welcomeemail', 'timemodified');
         
         if(!$field){
             
          $table = new xmldb_table('lp_welcomeemail');
          $field = new xmldb_field('timemodified');
          $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'email_sent');
          
          $dbman->add_field($table, $field);   
          
          upgrade_plugin_savepoint(true, 2012051500,'local','dlelegacytools');

         }else{
             mtrace('timemodified field already exists.');
             upgrade_plugin_savepoint(true, 2012051500,'local','dlelegacytools');
         }
     }else{
        mtrace('lp_welcomeemail table does not exist to add new field.');
        upgrade_plugin_savepoint(true, 2012051500,'local','dlelegacytools');
     }
 }
 
 return true;
 
}
  
?>

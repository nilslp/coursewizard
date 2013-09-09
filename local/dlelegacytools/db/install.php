<?php
		
/**
* Function Name: lpscheduler_add_instance 
* Description: Installing the default parameters for the scheduler plugin
* @return none;
*/
function xmldb_local_dlelegacytools_install() {
    global $DB;
    
    /** SCHEDULER PLUGIN DEFAULT SETTINGS**/
        // default settings for plugin: 0 off 1 on
        $scheduler_items = new object();

        // most settings are conrtolled with on off switch 0=off 1=on
        $scheduler_items->runExtras = 'true';
        $scheduler_items->disablescheduledbackups = 'false';		
        $scheduler_items->removeExpiredEnrolments = 'true';
        $scheduler_items->longtimenosee = 'true';
        //$scheduler_items->deleteIncompleteProfile = 0; // custom one added in and changed to deleteincompleteusers for m2
        $scheduler_items->deleteincompleteusers = 'true';
        $scheduler_items->deleteunconfirmed = 'true';
        $scheduler_items->loglifetime = 'true';
        $scheduler_items->notifyloginfailures = 'nonotifications'; // this is default setting could be between no notifications / mainadmins /alladmins          			
        $scheduler_items->sendNewPasswords = 'true';
        $scheduler_items->forceContext = 'true';
    // $scheduler_items->runEnrolment = true; // code that was previously written replaced in m2 inside /lib/enrollib.php
        $scheduler_items->runAuth= 'true';
        $scheduler_items->disablestatsprocessing = 'true'; 	
        $scheduler_items->sendForumEmails = 'true';
        $scheduler_items->forumEmailFrom = 'true';
        $scheduler_items->blockwelcomeemail = 'false';

        $lpscheduler = new object();

        foreach( $scheduler_items as $key=>$value ){

            $lpscheduler->plugin = 'lpscheduler';
            $lpscheduler->name = $key;
            $lpscheduler->value = $value;            
            try {
                // initialise db transaction.
                $transaction =  $DB->start_delegated_transaction(); 
                $where = array('plugin'=>$lpscheduler->plugin, 'name'=>$lpscheduler->name);
               
                $existing_configs = $DB->get_records('config_plugins',$where);
                if(empty($existing_configs)){
                
                    $output = $DB->insert_record('config_plugins',$lpscheduler,true);	
                    $transaction-> allow_commit();	
                }else{
                    $output = "configuration settings already exist in the database for this plugin.";
                    //$DB->update_record('config_plugins',$lpscheduler,true);
                }
            } catch(Exception $e){
                $transaction->rollback($e);
            }
        }

        
        if ($output) {
            // Default on installation that the deletion of modules and courses
            // must always be confirmed
            $output = set_config('confirmdelete', '1', 'local/dlelegacytools');            
        }
        
        // Default configs
        set_config('confirmdelete', '1', 'local/dlelegacytools');
        set_config('hiddenprofilefields', 'on', 'local/dlelegacytools');
        set_config('questcoursebtn', 'show', 'local/dlelegacytools');
        set_config('scormhideenterbtn', 'on', 'local/dlelegacytools');
        set_config('scormpopup', 'show', 'local/dlelegacytools');
        set_config('scormredirect', 'coursetopic', 'local/dlelegacytools');
        set_config('scormscoremessage', 'scoreonly', 'local/dlelegacytools');
        
    
    /** END SCHEDULER SETTINGS INSTALL**/
        $welcome_email_items = new stdClass();
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
        
        return $output;
  
}
?>

<?php
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/local/dlelegacytools/lang/en/local_dlelegacytools.php');

/** Welcome email functions class
 * @author: Rachael Harkin**/

class lpscheduler_welcomeemail_lib {
    
    /**
     * @global moodle_database $DB
     */
    function get_email_items(){
        global $DB;
     
        $email_items = $DB->get_records('config',array('name'=>'autoEmail OR', 'name'=>'autoEmailText OR', 'name'=>'autoEmailCc OR','name'=>'autoEmailFrom OR','name'=>'autoEmailSubject') );
        
        if(!empty($email_items)){
             return $email_items;               
        }else{
            return false;
        }
    }
    
    
    function welcomeemail_get_instance_records(array $row_id=null){
            global $DB;

            if($row_id != null){

                foreach($row_id as $row){
                    $results = $DB->get_record('config_plugins',array('id'=>$row),'*');
                }

            }else{
                   
                $results = $DB->get_records('config_plugins',array('plugin'=>'lpscheduler_welcomeemail'),'id ASC');
            }

            //$records = lpscheduler_lib::get_unserialize_records($results);

            return $results;
    }
    /**
     * Name: welcomeemail_get_configs
     * @global type $CFG
     * @return \stdClass of objects
     */
    function welcomeemail_get_configs(){
        
        $configs = $this->welcomeemail_get_instance_records();
       
        if(!empty($configs)){
 
            $config_list = new stdClass();
           
            foreach($configs as $con){ 
                $config_list->{$con->name} = $con->value;
                $config_list->id = $con->id;
            }
        }  
        return $config_list;
     }
     
     /**
      * Name : welcomeemail_update_instance
      * @global type $CFG
      * @global type $DB
      * @param type $vars
      * @return boolean 
      */

    function welcomeemail_update_instance($vars){
        global $DB;
     //   print_r($vars);
        if( !empty($vars)){
        
            $configs = $this->welcomeemail_get_instance_records();
            $welcomeemail = new stdClass();
            $update = false;
            
            foreach($vars as $key=>$value){

                foreach($configs as $con){

                    if ( $con->name == $key ){
                        $id = $con->id;
                    }
                }

                $welcomeemail->id = $id;
                $welcomeemail->plugin = 'lpscheduler_welcomeemail';
                $welcomeemail->name = $key;
                $welcomeemail->value = htmlentities($value, ENT_COMPAT, 'UTF-8');       

                # You may have to add extra stuff in here #
                    #$transaction->
                if ($key != 'submitbutton'){
                    $update = $DB->update_record('config_plugins', $welcomeemail,true);
                }
            }
            if(!$update){
                return false;
            }else{
                return true;   
            }
        }
        
    }
    
    // Name : welcomeemail_updatetable 
    // DESCRIPTION: Used as part of the cron to update lp_welcomeemail table with new users.
    // !!!NOTE: NO LONGER IN USE!!! but kept in case it is needed again.
    // overtaken by the event handler in /dlelegacytools/eventhandlers.php
    // which updates the lp_welcomeemail table when a new user is created
    // the /db/upgrade.php file will so a bulk push of users to the table
    // when welcomeemail plugin is initialised. rh 09.05.2012
    
    function welcomeemail_updatetable(){
        global $DB;
        
        $dbman = $DB->get_manager();
        $table = $dbman->table_exists('lp_welcomeemail');
          
        if($table){

        // for dbs that have the welcomeemail field grab the value of this field and populate the new table
        // created.
        $welcome_field = $dbman->field_exists('user', 'welcomeemail');

        if($welcome_field){
                  
            // user table
            $users =  $DB->get_records_sql('SELECT ID, welcomeemail FROM {user}');
           
            if(!empty($users)){

                foreach($users as $rec ){

                    $exists = $DB->record_exists('lp_welcomeemail', array( 'userid'=>$rec->id ) ); 

                    if(!$exists){
                        $DB->insert_record('lp_welcomeemail', array( 'userid'=>$rec->id,'email_sent'=>$rec->welcomeemail,false ) ); 

                    }else{

                        $DB->set_field('lp_welcomeemail', 'email_sent',$rec->welcomeemail, array('userid'=>$rec->id) ); 

                    }
                        $DB->set_field('user', 'welcomeemail',1, array('id'=>$rec->id) ); 
                }
            }
        }
     }
   }

    
    /**
     *
     * @global type $DB
     * @param type $newUsers 
     */
    
    function default_email($newUsers=null,$authority=''){
        global $DB,$CFG,$SITE;
            // deafult email settings
            if ( !empty( $newUsers ) ){

                foreach( $newUsers as $newUser ){
                    
                    $checkemail = $this->check_email( $newUser->email );
                    
                        if ( $newUser->auth == 'manual' ){                     
                      
                                if ( $mail_configs->enable_email_template == 'true') {

                                    $to = $newUser->email;

                                    if(empty($emailSubject->value)) {
                                        $subject = "Your DLE login details";
                                    }
                                    else {
                                        $subject = $emailSubject->value;
                                    }

                                    $header  = "From:".$mail_configs->new_user_email_from."\n";
                                    $header .= "Cc: ".$mail_configs->new_user_cc . "\n";
                                    $body = "Hi ". $newUser->firstname . "\n\n";
                                    $body .= $mail_configs->new_user_email_body;

                                if(trim($emailContent->value) != ""){
                                        $body = str_replace('<username>',$newUser->username,$body);

                                    if($newUser->emailstop == 0){
                                        
                                        if($checkemail == false){
                                            echo $body."<br />";
                                            // mail($to, $subject, $body, $header);
                                            return email_to_user($to, $supportuser, $subject, $message, $messagehtml);
                                        }else{
                                            mtrace( "mail NOT SENT to: ".$to);
                                        }
                                    }
                                }
                                }else{
                                    $to      = $newUser->email;
                                    $subject = "Your DLE login details " . $authority . " DLE";

                                    $body = "Hi ". $newUser->firstname . "\n";
                                    $body .= "You've been added to the ".$authority." Dynamic Learning Environment (DLE) to access your elearning. \n";
                                    $body .= "To access the DLE go to ". $CFG->wwwroot ." and login with your username and password.\n\n";

                                    $body .= "Your login details\n";
                                    $body .= "Username: ".$newUser->username."\n";
                                    $body .= "Password: welcome (you will be asked to change this the first time you login).\n\n";

                                    $body .= "If you have any problems contact support@learningpool.com, do not reply to this message.\n\n";

                                    $body .= "Regards\n".$authority." DLE administrator.";

                                    $header  = "From: support@learningpool.com\n";

                                    if($newUser->emailstop == 0){
                                        echo $body."<br />";

                                        if($checkemail == false){
                                            mail($to, $subject, $body, $header);
                                        }else{
                                        mtrace( "mail NOT SENT to: ".$to);
                                        }
                                    }
                             }
                        }
                            
                        $rec_id = $DB->get_record('lp_welcomeemail',array('userid'=>$newUser->id),'*'); 

                        $userinfo = new stdClass();                           
                        $userinfo->id = $rec_id->id;
                        $userinfo->email_sent = 1;
                        $userinfo->timemodified = time();
                        
                        mtrace( "updating user ".$newUser->username." email_sent value for default email");  

                        $DB->update_record('lp_welcomeemail',$userinfo,true);

                    }
            }       
    }
    
    /**
     *
     * @global type $DB
     * @param type $newUsers 
     */
    
    function custom_newUserEmail($newUsers=null, $mail_configs=null, $authority=''){
        global $DB, $CFG;
        
      //  $emailAuto = $DB->get_record('config',array('name' => 'autoEmail'));        
        $emailCc = $mail_configs->new_user_cc;     
        $emailSubject = $mail_configs->new_user_subject;
        
        if(empty( $emailSubject )) {
            $subject = "Your DLE login details";
        }else {
            $subject = $emailSubject;
        }
       
        if( !empty($newUsers) ){
            // manual users
            foreach( $newUsers as $newUser ){
                
                $checkemail = $this->check_email($newUser->email);

            // $emailstop = $DB->get_field('user','emailstop',array('userid'=>$newUser->id),IGNORE_MISSING); 

                if ( $newUser->auth == 'manual' ){

                    $emailContent = html_entity_decode($mail_configs->new_user_email_body,ENT_COMPAT,'UTF-8');
                    $emailFrom = $mail_configs->new_user_email_from;

                    if ( $mail_configs->enable_email_template == 'true' ) {

                        $to = $newUser->email;

                        $header  = "From:".$emailFrom."\n";
                        $header .= "Cc: ".$emailCc. "\n";
                        $body = "Hi ". $newUser->firstname . "\n\n";
                        $body .= $emailContent;

                        if(trim($emailContent) != ""){
                            $body = str_replace('<username>',$newUser->username,$body);

                        if($newUser->emailstop == 0){
                                                       
                            if($checkemail == false){
                                echo $body."<br />"; 
                                mail($to, $subject, $body, $header);
                            }else{
                                mtrace( "mail NOT SENT to: ".$to);
                            }
                        }

                        // update record
                        $rec_id = $DB->get_record('lp_welcomeemail',array('userid'=>$newUser->id),'*'); 

                        $userinfo = new stdClass();
                        $userinfo->id = $rec_id->id;
                        $userinfo->email_sent = 1;
                        $userinfo->timemodified = time();
                        
                         mtrace( "updating user ".$newUser->username." email_sent value for auth=manual");     

                        $DB->update_record('lp_welcomeemail',$userinfo,true);
                        
                    }
                }
            }else{
                // Self registration Email
                if( $newUser->auth == 'email' ){
                    // 23 August 2012
                    // This code block was ammended as it resulted in two confirmation
                    // emails being sent
                    $emailFrom = $mail_configs->new_user_email_from;
                    $emailCc = $mail_configs->self_reg_email_cc;
                    
                    if (isset($emailCc)) {
                        // Send the email to the defined CC address
                        $header  = "From:".$emailFrom."\n";
                        $header .= "Cc: ".$emailCc. "\n";                       
                                                
                        // The following code mimics the behaviour of moodlelib.php's
                        // send_confirmation_email function
                        $site = get_site();

                        $data = new stdClass();
                        $data->firstname = fullname($newUser);
                        $data->sitename  = format_string($site->fullname);
                        $data->admin     = generate_email_signoff();

                        $subject = get_string('emailconfirmationsubject', '', format_string($site->fullname));

                        $username = urlencode($newUser->username);
                        $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
                        
                        if ($mail_configs->self_reg_welcome_link == 'true') {
                            $data->link  = $CFG->wwwroot .'/login/confirm.php?data='. $user->secret .'/'. $username;
                        }
                        else {
                            $data->link = '';
                        }

                        $message     = get_string('emailconfirmation', '', $data);
                        $messagehtml = text_to_html(get_string('emailconfirmation', '', $data), false, false, true);

                        $user->mailformat = 1;  // Always send HTML version as well

                        // dont send email is emailstop is off but still update welcomeemail field.
                        if ($newUser->emailstop == 0) {
                            if ($checkemail == false) {
                                echo $body."<br />"; 
                                $to = ''; // When email auth is used the user shouldn't receive the email twice
                                mail($to, $subject, $message, $header);
                            }
                            else {
                                mtrace("CC'd mail NOT SENT to: " . $emailCc);
                            }
                        }
                    }
                    
                    $rec_id = $DB->get_record('lp_welcomeemail', array('userid' => $newUser->id), '*'); 

                    $userinfo = new stdClass();
                    $userinfo->id = $rec_id->id;
                    $userinfo->email_sent = 1;
                    $userinfo->timemodified = time();
                    
                    mtrace( "updating user ".$newUser->username." email_sent value for auth=email");    

                    $DB->update_record('lp_welcomeemail',$userinfo,true);
                    
                }else{
                    
                    $to      = $newUser->email;
                    $subject = "Your DLE login details " . $authority . " DLE";

                    $body = "Hi ". $newUser->firstname . "\n";
                    $body .= "You've been added to the ".$authority." Dynamic Learning Environment (DLE) to access your elearning. \n";
                    $body .= "To access the DLE go to ". $CFG->wwwroot ." and login with your username and password.\n\n";

                    $body .= "Your login details\n";
                    $body .= "Username: ".$newUser->username."\n";
                    $body .= "Password: welcome (you will be asked to change this the first time you login).\n\n";

                    $body .= "If you have any problems contact support@learningpool.com, do not reply to this message.\n\n";

                    $body .= "Regards\n".$authority." DLE administrator.";

                    $header  = "From: support@learningpool.com\n";

                    if($newUser->emailstop == 0){

                        if($checkemail == false){
                            echo $body."<br />";
                            mail($to, $subject, $body, $header);
                        }else{
                            mtrace( "mail NOT SENT to: ".$to);
                        }
                    }

                    $rec_id = $DB->get_record('lp_welcomeemail',array('userid'=>$newUser->id),'*'); 

                    $userinfo = new stdClass();
                    $userinfo->id = $rec_id->id;
                    $userinfo->email_sent = 1;
                    $userinfo->timemodified = time();
                     
                    mtrace( "updating user ".$newUser->username." email_sent value");    
                    
                    $DB->update_record('lp_welcomeemail',$userinfo,true);            
                }
            }
        }
      }else{
            mtrace("No users to email");
        }
    }
    
    function check_email($email=''){
        //REFERENCED TABLES {moodleadmin.rpt_email_ignore} ARE TO BE REMOVED
        //CHANGED TO {return false} BY DMCD 20/06/2013
        return false;
    }
    /**
     * Name: emailNotify
     * @global type $DB 
     * @global type $CFG 
     * @global type $SITE 
     */

    function emailNotify(){
        global $DB, $CFG,$SITE;
                
        // DPMH - plugin is deprecated. See local/welcome_email instead.
        // code execution should never find it's way to this function, but just to be safe ...
        return;

        $authority = $SITE->fullname;
        
        $sqlDelUsers = "SELECT id FROM {user} WHERE deleted=?";
        $delUsers = $DB->get_records_sql( $sqlDelUsers,array('1') );
        
        $sql_lpwelUsers = "SELECT userid FROM {lp_welcomeemail} WHERE email_sent=?";
        $lpweUsers = $DB->get_records_sql( $sql_lpwelUsers,array('0') );
           
        // update lp_welcomeemail before emails get sent setting deleted users email_sent to 1
        foreach($delUsers as $duser){

            $users_match = array_key_exists($duser->id,$lpweUsers);
            
            if( $users_match === true ){
                
                mtrace( "match exists for deleted user...updating user record on lp_welcomeemail " );
                
                $rec_id = $DB->get_record( 'lp_welcomeemail',array('userid'=>$duser->id),'*' ); 
                
                $userinfo = new stdClass();
                $userinfo->id = $rec_id->id;
                $userinfo->email_sent = '1';
                $userinfo->timemodified = '0';

                $DB->update_record('lp_welcomeemail',$userinfo,true);
            }
        }
        
        /*
        * Select only required fields, as mass select kills the script
        * - as we're not sure what's new upper limit on mass email the query is limited to arbitrary 2000 records
        * - for larger mass emails, run the script multiple times in a row
        */
        /*
        * [14/6/2010] Update google server blocks at 2k emails, set limit to 1k - rwm
        */

        //$sqlNewUsers = "SELECT id,auth,email,firstname,username FROM {user} WHERE `welcomeemail` = ? AND `emailstop` = ? LIMIT 1000";
        
        $sqlNewUsers = "SELECT mu.*
                        FROM {user} mu
                        JOIN {lp_welcomeemail} lpwel ON mu.id = lpwel.userid
                        WHERE  lpwel.email_sent = ? AND mu.deleted=?";
        
        $newUsers = $DB->get_records_sql( $sqlNewUsers,array('0','0') );
        
        if ( !empty( $newUsers ) && !empty( $authority ) ){

            $we_global_config = get_config('lpscheduler','blockwelcomeemail');  // if welcome emails are on or off ( true or false )
             
            if( $we_global_config == 'true' ){
                
                 $email_template = $this->welcomeemail_get_configs();

                 if( $email_template->enable_email_template == 'true' ){   // if welcome email template is being used then build the email for the customised content          
                    
                     mtrace( "Sending " . count( $newUsers ) . " emails ..." );
                    $this->custom_newUserEmail( $newUsers, $email_template, $authority ); 
                 }else{
                    mtrace( "Using default Email : sending" . count($newUsers) ."emails." );
                    $this->default_email( $newUsers, $authority ); // or use the default email.
                }       
            }else{
                // prevent emails getting sent when global setting accidently turned on.
                mtrace(" Not sending email but updating user welcome email value. Global setting is off. Updating ". count($newUsers)." users");
                
                foreach($newUsers as $newUser){
                    
                    $rec_id = $DB->get_record('lp_welcomeemail',array('userid'=>$newUser->id),'*'); 

                    $userinfo = new stdClass();
                    $userinfo->id = $rec_id->id;
                    $userinfo->email_sent = 1;
                    $userinfo->timemodified = time();

                    $DB->update_record('lp_welcomeemail',$userinfo,true);
                }
            }
        }
    }
    
}


/**
 * New class for welcome email interface 
 */
class local_lpscheduler_welcomemail_form extends moodleform {
        
    function definition(){

        $configs = get_config('lpscheduler_welcomeemail');
        $email_lib = new lpscheduler_welcomeemail_lib();
        $email_items = $email_lib->get_email_items();
        
        $mform = &$this->_form;
        $mform->addElement('header', 'welcomeemail-heading', get_string('welcomeemail_menu_name','local_dlelegacytools'));
        
        echo html_writer::tag('p',get_string('welcome_index_desc','local_dlelegacytools'));

        $attr = new stdClass();
  
        if(!empty($configs)){
        
            echo html_writer::start_tag('div',array('class'=>'form-setting')); 
            
            // for each config item apply its current setting and build its label and description for user.
            foreach($configs as $key => $item){
               
                $item = html_entity_decode($item,ENT_COMPAT,'UTF-8');
              
                // set the value and name fields of the specific configs aswell.
                 
                if($key == 'schedulewelcomeemail' ) {
                    echo html_writer::start_tag('div', array( 'class'=>'date-selector' ) );
                        $mform->addElement('text','schedulewelcomeemail', get_string($key,'local_dlelegacytools'), array('maxlength'=>'4') );
                    echo html_writer::end_tag('div');
                }else{
                
                    if($key == 'enable_email_template' || $key == 'self_reg_welcome_link'){    

                        if($item == 'true'){
                            $attr->options = array ('true'=>'on', 'false'=>'off');
                        }else{
                            $attr->options = array('false'=>'off','true'=>'on');
                        }
                            $mform->addElement('select',$key,get_string($key,'local_dlelegacytools'), $attr->options); 
                    }else{                  

                        if($key == 'new_user_cc' || $key == 'new_user_email_from' || $key == 'new_user_subject' || $key == 'self_reg_email_cc'){

                        //  $description = $this->get_field_description($key);

                        /* echo html_writer::start_tag('div',array('class'=>'field-description'));
                            echo html_writer::tag('p',$description,array('class'=>'description'));
                            echo html_writer::end_tag('div');*/
                        $mform->addElement('text',$key,get_string($key,'local_dlelegacytools'),array('value'=>$item) );
                            $mform->setType($key, PARAM_RAW);

                        }else{

                            $mform->addElement('textarea',$key,get_string($key,'local_dlelegacytools'),array ('value'=>$item ) );
                            $mform->setType($key, PARAM_RAW);
                            $mform->setDefault($key, $item);
                        }
                }
                }
            }
                    
        echo html_writer::end_tag('div');
        $this->add_action_buttons();
        }          
     }
     
     function get_field_description($key) {
         
         if( !empty($key) ){
            
             $description = "";
             
            // new user descriptions
             if ($key == 'enable_email_template'):$description = get_string('new_user_checkbox','local_dlelegacytools');endif;
             if ($key == 'new_user_cc'):$description = get_string('new_user_cc','local_dlelegacytools');endif;
             if ($key == 'new_user_email_body'):$description = get_string('new_user_email_body','local_dlelegacytools');endif;
             if ($key == 'new_user_email_from'):$description = get_string('new_user_email_from','local_dlelegacytools');endif;
             
             // self reg descriptions
             if ($key == 'self_reg_welcome_link'):$description = get_string('self_reg_welcome_link','local_dlelegacytools');endif; 
             if ($key == 'self_reg_email_cc'):$description = get_string('self_reg_email_cc','local_dlelegacytools');endif;
             if ($key == 'self_reg_confirmation_text'):$description = get_string('self_reg_confirmation_text','local_dlelegacytools');endif;
             if ($key == 'self_reg_email_body'):$description = get_string('self_reg_email_body','local_dlelegacytools');endif; 
             
             return $description;
                           
         }
       
     }
     
        
}?>

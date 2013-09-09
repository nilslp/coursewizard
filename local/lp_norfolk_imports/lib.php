<?php


 
// On 1 February 2013, delete all accounts tmp1301xx and sc1301xx
// On 1 March 2013, delete all accounts tmp1302xx and sc1302xx
// On 1 April 2013, delete all accounts tmp1303xx and sc1303xx
function lp_norfolk_do_delete_temp_users(){
    global $DB;
    $timelastmonth = strtotime( '-1 month', time() );
    $date = date('ym', $timelastmonth);
    $rs = $DB->get_records_select('user', ' deleted = 0 AND (username LIKE ? OR username LIKE ? ) ', array( "tmp$date%" , "sc$date%" ) );
    foreach($rs as $user){
        lp_norfolk_soft_delete($user->username, $user->id);
    }
}



function lp_norfolk_init_data_table(){
    global $DB;
    //find out if it's been done before
    $done_already = $DB->count_records('lp_norfolk_data');
    if($done_already > 1000){
        return true;
    }
    $timelastweek = strtotime( '-7 day', time() );
    $lastweek = date('Ymd', $timelastweek);
    //get all the users and their hierarchy data and dump them into the norfolk table
    $sql = 'INSERT INTO {lp_norfolk_data} (username, email,  firstname, lastname, existing_user_id, datecreated)
            SELECT u.username, u.email,  u.firstname, u.lastname, u.id, '.$lastweek.' as datecreated FROM {user} u
            WHERE  username REGEXP \'^[0-9]\' AND deleted = 0   ';
    $DB->execute($sql);
}

function lp_norfolk_absorb_file( $file ){
    global $DB;
    $debug = false;
    if ($debug) {
        $errorhandle = fopen('errors.log', 'w');
    }

    $message_to_billy = array();

    //first find out the maximum date already in the table - this allows us to do some checking
    $biggest_date_in_table = $DB->get_record_sql('SELECT Max(datecreated) as d FROM {lp_norfolk_data}');
    if(!$biggest_date_in_table){
        $biggest_date_in_table = 0;
    }else{
        $biggest_date_in_table = $biggest_date_in_table->d;
    }
    $today                 = date('Ymd');
    //if the biggest_date_in_table is the same as today then there must be a foul-up because this function lp_norfolk_is
    //called to absorb a complete file and we shouldn't be getting 2 a day
    if( $today == $biggest_date_in_table){
        if($debug){
            fwrite($errorhandle, "Already entered data today\n");
        }
        return false;
    }
    $filehandle = fopen($file, 'r');
    $rowcounter = 0;
    //get row by row, correct the hierarchies and shove the result into a database table
    while($row = fgetcsv($filehandle)){
 
        if( empty($row) ){
            //this gets us around the possible problem of the first row being empty
            continue;
        }
        $rowcounter++;

        
        if($rowcounter == 1){
           //this is the first row or else we're jiggered before we even get going
           
            /*
                Expected columns are something like these...
                    EmployeeNumber .... = username
                    L1 Org Name ...     = hierarchy_level_1
                    L2 Org Name ...     = hierarchy_level_2
                    Employee First Name = firstname
                    Employee Last Name  = lastname
                    Email Address       = email
           */
            //we can't trust them to be the same every time so we need to map them
            $colcounter = 0;
            foreach($row as $col){
                if( empty($col) ){
                    $colcounter++;
                    continue;
                }
                if(stripos($col, 'mail') !== false ){
                    define('LPNEMAIL', $colcounter);                     
                }
                else if(stripos( $col, 'number') !== false ){                   
                    define('LPNUSERNAME', $colcounter);
                }
                else if(stripos($col, 'org') !== false ){
                    if(stripos($col, '1' ) !== false){
                         define('LPNHIERARCHY_LEVEL_1', $colcounter);
                    }else{
                        define('LPNHIERARCHY_LEVEL_2', $colcounter);
                    }
                }
                else if(stripos($col, 'name' ) !== false ){
                    if(stripos($col, 'first' ) !== false){
                        define('LPNFIRSTNAME', $colcounter);
                    }else if(stripos($col, 'last') !== false ){
                        define('LPNLASTNAME', $colcounter);
                    }
                }

                $colcounter++;
            }
            //if any of the expected columns didn't map out then we have a foul-up - tell Billy and die
            if( !defined('LPNUSERNAME') ||
                !defined('LPNHIERARCHY_LEVEL_1') ||
                !defined('LPNHIERARCHY_LEVEL_2')||
                !defined('LPNEMAIL') ||
                !defined('LPNFIRSTNAME') ||
                !defined('LPNLASTNAME')){
                    $message_to_billy[] = 'The columns in the csv didn\'t map out as expected and we can\'t read the data. Sorry. Can you have a look?'; 
                    lp_norfolk_send_error_email($message_to_billy);
                    return false;
            }
        }else{
            //subsequent rows
            $shadowuser                    = new stdClass();
            $shadowuser->firstname         = $row[LPNFIRSTNAME];
            $shadowuser->lastname          = $row[LPNLASTNAME];
            $shadowuser->email             = $row[LPNEMAIL];
            //add leading zeroes
            $shadowuser->username          = sprintf('%05s',  $row[LPNUSERNAME] );
            $shadowuser->hierarchy_level_1 = $row[LPNHIERARCHY_LEVEL_1];
            $shadowuser->hierarchy_level_2 = $row[LPNHIERARCHY_LEVEL_2];
            //we'll be using the date to distinguish this user from the same person in other uploads (clever, eh?)
            $shadowuser->datecreated       = date('Ymd');
            $shadowuser->id = $DB->insert_record('lp_norfolk_data', $shadowuser, true);

            //decide whether to update or add to the moodle tables
            //this tells us whether we have a record already from the last upload
            $previous_entry = $DB->get_records_select('lp_norfolk_data', ' username = ? AND datecreated = ? ', array($shadowuser->username, $biggest_date_in_table), ' id DESC ', '*', 0, 1 );
            $count_new_users = 0;
            if( !$previous_entry ){
                //check this boyo in the moodle tables
                //if exists, then update
                $existing_user = $DB->get_record_select('user', ' username = ? ', array( $shadowuser->username ), 'id' );
                if($existing_user){
                    $shadowuser->existing_user_id = $existing_user->id;

                     
                    $updateobject = new stdClass();
                    $updateobject = $shadowuser;

                    //the $updateobject has to have the correct id
                    $updateobject->id = $shadowuser->existing_user_id;
                    //don't overwrite email with blank
                    if( empty($updateobject->email) ){
                        unset($updateobject->email);
                    }
 
                    $DB->update_record('user', $updateobject);
                    unset($updateobject);
                }
                //else doesn't exist in DLE so create user and get id number
                else{
                    $count_new_users++;
                    //hard-code mnethostid
                    $mnethostid = 3;

                    $userobject               = new stdClass();
                    $userobject->email        = $shadowuser->email;
                    $userobject->username     = $shadowuser->username;
                    $userobject->firstname    = $shadowuser->firstname;
                    $userobject->lastname     = $shadowuser->lastname;
                    $userobject->city         = 'Norfolk';
                    $userobject->country      = 'GB';
                    $userobject->deleted      = 0;
                    $userobject->confirmed    = 1;
                    $userobject->timemodified = time();
                    $userobject->timecreated  = time();
                    $userobject->mnethostid   = $mnethostid;
                    $userobject->password     = hash_internal_user_password('welcome');
        
                    $shadowuser->existing_user_id = $DB->insert_record('user', $userobject, true);
                    
                    //add to mdl_lp_welcomeemail
                    lp_norfolk_set_dle_welcomemail( $shadowuser->existing_user_id );
                    set_user_preference('auth_forcepasswordchange', 1, $shadowuser->existing_user_id);

                }
                //update the existing_user_id in the shadow table
                $fred = $DB->set_field('lp_norfolk_data', 'existing_user_id', $shadowuser->existing_user_id, array('id'=>$shadowuser->id) );
                
                //lookup the hierarchy number
                $hierarchyid = lp_norfolk_lookup_hierarchy( $shadowuser->hierarchy_level_1, $shadowuser->hierarchy_level_2 );
                if($hierarchyid){
                    $shadowuser->hierarchyid = $hierarchyid;
                    //add hierarchy
                    $newuser = !$existing_user;
                    lp_norfolk_set_dle_hierarchy($shadowuser, $shadowuser->existing_user_id, $newuser);
                }else{
                    //add this to an email for Billy
                    $message_to_billy[] = 'Could not find hierarchy to match '.$shadowuser->hierarchy_level_1.' '. $shadowuser->hierarchy_level_2.' for user '.$shadowuser->username;
                }
            }else{
                $previous_entry = array_shift($previous_entry);
                //this isn't supposed to happen, but just in case...
                if(empty($previous_entry->existing_user_id)){                    
                    $existing_user = $DB->get_record_select('user', ' username = ? ', array( $shadowuser->username ), 'id' );
                    if($existing_user){
                        $previous_entry->existing_user_id = $existing_user->id;
                    }else{
                        continue;
                    }
                }
                $shadowuser->existing_user_id = $previous_entry->existing_user_id;
                $DB->update_record('lp_norfolk_data', $shadowuser);
                if( lp_norfolk_objects_match_bar_date($shadowuser, $previous_entry) ){
                    //ignore because it's the same as last week's entry 

                    
                }else{
                    //It must be an update.
                    //update user record            

                    $updateobject = new stdClass();
                    $updateobject = $shadowuser;

                    //the $updateobject has to have the correct id
                    $updateobject->id = $shadowuser->existing_user_id;
                    //don't overwrite email with blank
                    if( empty($updateobject->email) ){
                        unset($updateobject->email);
                    }
                    $DB->update_record('user', $updateobject);
                    unset($updateobject);                    
                    //lookup the hierarchy number
                    $shadowuser->hierarchyid = lp_norfolk_lookup_hierarchy( $shadowuser->hierarchy_level_1, $shadowuser->hierarchy_level_2 );
                    //update hierarchy
                    lp_norfolk_set_dle_hierarchy($shadowuser, $shadowuser->existing_user_id, false);
                }
            }
        }        
    }
    $message_to_billy[] = "There are $rowcounter users in the spreadsheet";
    $message_to_billy[] = "$count_new_users are new users and have been added";
    
    //soft delete any users who don't appear on this csv but were on the last one
    $sql = 'SELECT username, existing_user_id FROM {lp_norfolk_data} d1 WHERE datecreated = ? AND username NOT IN (SELECT username FROM {lp_norfolk_data} WHERE datecreated = ? )';

    $delete_these_users = $DB->get_records_sql($sql, array($biggest_date_in_table, $today) );
    $deletes_message = "These users were soft-deleted: ";
    if($delete_these_users){
        foreach($delete_these_users as $deluser){
            lp_norfolk_soft_delete($deluser->username, $deluser->existing_user_id);
            $deletes_message .= $deluser->username.", ";
        }
    }    
    $message_to_billy[] = $deletes_message;
    //and finish by sending errors if there are any
    if( count($message_to_billy) ){

        if($debug){
            fwrite($errorhandle, print_r($message_to_billy, true) );
        }
        lp_norfolk_send_error_email($message_to_billy);
    }
    if($filehandle){
        fclose($filehandle);
    }
    if($errorhandle){
        fclose($errorhandle);
    }
}

function lp_norfolk_set_dle_welcomemail($dleuserid)
{
    global $DB;
    $dataobj               = new stdClass();
    $dataobj->userid       = $dleuserid;
    $dataobj->email_sent   = 1;
    $dataobj->timemodified = time();
    return $DB->insert_record('lp_welcomeemail', $dataobj);
}

function lp_norfolk_objects_match_bar_date($obj1, $obj2){
    //needs to ignore id and datecreated  
 
    if( $obj1->email == $obj2->email &&
        $obj1->hierarchy_level_1 == $obj2->hierarchy_level_1 &&
        $obj1->hierarchy_level_2 == $obj2->hierarchy_level_2 &&
        $obj1->firstname == $obj2->firstname &&
        $obj1->lastname == $obj2->lastname){
        return true;
    }else{
        return false;
    }
}

function lp_norfolk_lookup_hierarchy( $hierarchy_level_1, $hierarchy_level_2 ){
    global $DB;
    //first we have to look up the level_1 - then the level_2
    $level_1 = $DB->get_records_select('lp_norfolk_lookup', ' hierarchy_level_1 = ?  ', array($hierarchy_level_1));

    if(count($level_1) == 1){
        //then there's nothing else to do - just return the ID
        $level_1 = array_shift($level_1);
        return $level_1->hierarchyid;
    }else{

        $rs = $DB->get_record_select('lp_norfolk_lookup', ' hierarchy_level_1 = ? AND hierarchy_level_2 = ? ', array($hierarchy_level_1, $hierarchy_level_2), 'hierarchyid' );
        if($rs){
            return $rs->hierarchyid;
        }else{
            $str = $hierarchy_level_1.' '. $hierarchy_level_2;
            return 15; //TODO this is a cop-out because we shouldn't be here
        }
    }  
}

function lp_norfolk_soft_delete($username, $id){
    global $DB;
    $user = $DB->get_record('user', array('id'=>$id));
    if($user->username != $username){
        //must be a foul-up   ---  don't do the delete
        return false;
    }else{
        $arr = array();
        //we musn't delete the ones from other hierarchies
        //get the hierarchies we can deal with
        $hierarchies = $DB->get_records_sql('SELECT DISTINCT hierarchyid FROM {lp_norfolk_lookup}');
        foreach($hierarchies as $hier){
            $arr[] = $hier->hierarchyid;
        }
        $good_hierarchies = implode(',', $arr);
        $check_hier = $DB->record_exists_sql('SELECT * FROM {lp_user_hierarchy} WHERE userid = ? AND hierarchyid IN(?) ', array($id,  $good_hierarchies ) );
        if($check_hier){
            //append ".DELETED" to username
            //append "." time() to email
            //set deleted =1
            //set timemodified = time()
            $user->username = $username . '.DELETED';
            $user->email = $user->email . '.' . time();
            $user->deleted = 1;
            $user->timemodified = time();

            return $DB->update_record( 'user', $user );
        }
        return false;
    }
}

function lp_norfolk_set_dle_hierarchy($shadowuser, $dleuserid, $newuser = false)
{
    global $DB;
    if ($newuser) {
        //check if there's a record to update in which case we don't need to add it
        if( $DB->record_exists_select( 'lp_user_hierarchy', ' userid = ? ', array(  $dleuserid ) ) ){
            return lp_norfolk_set_dle_hierarchy($shadowuser, $dleuserid, false);
        }
        $dataobj              = new stdClass();
        $dataobj->hierarchyid = $shadowuser->hierarchyid;
        $dataobj->userid      = $dleuserid;
        $DB->insert_record('lp_user_hierarchy', $dataobj);
        //get the dept id hierarchy by concatting the stuff in vw_lp_hierarchy_all
        $hierarchyset = $DB->get_record_sql("SELECT Concat_ws(',', hierarchyid1, hierarchyid2, hierarchyid3 ) AS hierarchy FROM  vw_lp_hierarchy_all WHERE hierarchyid = ?", array(
            $shadowuser->hierarchyid
        ));
        if ($hierarchyset) {
            $userfieldset         = $DB->get_record_sql("SELECT id FROM {user_info_field} f WHERE f.shortname = ?", array(
                'hierarchyid'
            ));
            $dataobj              = new stdClass();
            $dataobj->data        = $hierarchyset->hierarchy;
            $dataobj->userid      = $dleuserid;
            $dataobj->fieldid     = $userfieldset->id;
            $DB->insert_record('user_info_data', $dataobj);
        }
        return true;
    } else {
        //check there's a record to update otherwise we'll have to add it.
        if( !$DB->record_exists_select( 'lp_user_hierarchy', ' userid = ? ', array(  $dleuserid) ) ){
            return lp_norfolk_set_dle_hierarchy($shadowuser, $dleuserid, true);
        }
        if( !empty($shadowuser->hierarchyid) ){
            $DB->set_field('lp_user_hierarchy', 'hierarchyid', $shadowuser->hierarchyid, array(
                'userid' => $dleuserid
            ));
        }else  {
            print_r($shadowuser);
              
        }
        //get the dept id hierarchy by concatting the stuff in vw_lp_hierarchy_all
        $hierarchyset = $DB->get_record_sql("SELECT Concat_ws(',', hierarchyid1, hierarchyid2, hierarchyid3 ) AS hierarchy FROM  vw_lp_hierarchy_all WHERE hierarchyid = ?", array(
            $shadowuser->hierarchyid
        ));
        if ($hierarchyset) {
            $userfieldset = $DB->get_record_sql("SELECT d.id AS id FROM {user_info_field} f JOIN {user_info_data} d ON f.id = d.fieldid WHERE f.shortname = ? AND d.userid = ?", array(
                'hierarchyid',
                $dleuserid
            ));
            if ($userfieldset) {
                $DB->set_field_select('user_info_data', 'data', $hierarchyset->hierarchy, " id = $userfieldset->id ");
            }
        }
        return true;
    }
}


function lp_norfolk_send_error_email($message_array){

    $billy   = 'anthony.morley@norfolk.gov.uk,kerry.brundrit@norfolk.gov.uk';
    $subject = 'Automated User Import';
    $body    = implode("\r\n", $message_array);
    $headers = 'From: Cron at Norfolk<cron@noreply.com>';

    mail($billy, $subject, $body, $headers);
}


function local_lp_norfolk_imports_cron(){
    $debug = true;
    if ($debug) {
        $errorhandle = fopen('/www2/sites/norfolk.learningpool.com/wwwroot/local/lp_norfolk_imports/cronerrors.log', 'a');
        $today = date("Y-m-d h:i:s");
        fwrite($errorhandle, "\n-----------------------------------------".$today."-----------------------------------------\n");
    }
    $norfolk_plugin_turned_on = false;
    $norfolk_plugin_turned_on = (int)get_config('lp_local_norfolk_import', 'check_excel_user_import' );
    if( !$norfolk_plugin_turned_on ){
        fwrite($errorhandle, 'Plugin off');
        return;
    }

    //deleting the temp users
    lp_norfolk_do_delete_temp_users();

    //populate database table if it's empty
    lp_norfolk_init_data_table();


    //look for file in appropriate place
    if ($debug) {
        fwrite($errorhandle, "Looking for file\n");
    }
    $fs = get_file_storage();
    $files = $fs->get_directory_files(2, 'course', 'legacy', 0, '/HR_data/' );

    if($files){
        if ($debug) {
            fwrite($errorhandle,  "\nFound file for parsing\n");
        }
        //there'd better be only wan here cos we're supposed to be deleting them after we read them!
        foreach ($files as $f) {
            $file = $f;
        }
        if ($debug) {
            fwrite($errorhandle, "Between stages\n");
        }

        //if there's one there, transfer it somewhere else and remove it from Moodle
        if($file){

            $thisplace = '/www2/sites/norfolk.learningpool.com/wwwroot/local/lp_norfolk_imports';
            //open it with Perl and stick the data into a csv file that php can read
            $file->copy_content_to($thisplace.'/temp.xls');
            $password = 'lp'.date("my");
            exec('perl '.$thisplace.'/parseexcel.pl "'.$thisplace.'/temp.xls" "'.$password.'"');

            //that should have created "data.txt"
            if(file_exists($thisplace.'/data.txt')){
                if ($debug) {
                    fwrite($errorhandle, "\nFile data.txt created\n");
                }

                $lines_command = 'cat '.$thisplace.'/data.txt | wc -l'; 
                $lines = system($lines_command);
                //crash out if fewer than 8000 lines
                if( $lines < 8000 ){
                    if ($debug) {
                        fwrite($errorhandle, "Too few lines\n");
                    }
                    return;
                }  
                //and we can move temp.xls
                $today = date('Ymd');
                rename($thisplace.'/temp.xls', $thisplace.'/'.$today.'.xls');

                //then do all the stuff we want to do
                lp_norfolk_absorb_file($thisplace.'/data.txt');

                //then delete it too
                unlink($thisplace.'/data.txt');

                if ($debug) {
                    fwrite($errorhandle, "Remove moodle file\n");
                }
                $file->delete();

                return;
            }else{
                if ($debug) {
                    fwrite($errorhandle, "Didn't find data.txt so exiting\n");
                }
                return;
            }
        }else{
            if ($debug) {
                fwrite($errorhandle, "This is odd - we found files but don't have a file??\n");
            }
            return;
        }
    }else{
        if ($debug) {
            fwrite($errorhandle, "No files discovered\n");
        }
        return;
    }
    
}

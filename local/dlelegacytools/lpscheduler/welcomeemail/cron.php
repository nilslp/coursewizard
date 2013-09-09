<?php

require_once(dirname(__FILE__).'/lib.php');

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function lpscheduler_welcomeemail_cron(){
    // DPMH - plugin is deprecated. See local/welcome_email instead.
    return;
    
    $email_lib = new lpscheduler_welcomeemail_lib();
    $configs = $email_lib->welcomeemail_get_configs();
    
    $def_time = 1345;
    $curtime = date("Hi", time());
    $curtime = intval($curtime);

    if( $configs->schedulewelcomeemail > 0 ) {  

        $emailtime = intval($configs->schedulewelcomeemail);

        mtrace("Current Time: $curtime, Scheduled Time: $emailtime.");

        if($emailtime <= $curtime){
            mtrace('sending emails ...');
            $email_lib->emailNotify();
        }

    }else if($def_time <= $curtime){
          mtrace('sending default time emails ...');
        $email_lib->emailNotify();
    }
}?>


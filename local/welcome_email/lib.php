<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

/**
 * Checks for presence of lp_welcomeemail table and adds it if it does not exist
 * 
 * @global type $CFG
 * @global type $DB 
 */
function local_welcome_email_assert_table_exists() {
    global $CFG, $DB;    

    $dbman = $DB->get_manager();
    
    if (!$dbman->table_exists('lp_welcomeemail')) {
        
        $table = new xmldb_table('lp_welcomeemail');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('email_sent', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));

        $dbman->create_table($table);         
    }
}

/**
 * given an array of user ids, will update the sent status of the welcome email row for each user to the specified status
 * 
 * @global type $DB
 * @param array $userids
 * @param type $status
 * @return boolean - success of the operation
 */
function local_welcome_email_reset_status(array $userids, $status = 1) {
    global $DB;
    // validate status
    if (!in_array($status, array(0,1))) {
        return false;
    }
    
    // create IN clause
    $userids = array_filter($userids);
    if (empty($userids)) {
        return false;
    }
    
    $userids = implode(',', $userids);
    return $DB->execute("UPDATE {lp_welcomeemail} SET timesent = ?, timemodified = ?, email_sent = ?  WHERE userid IN ($userids)", array(0, time(), $status));
}

/**
 * Event handler for the user_created event. Adds a new record for the created user to track if emails have been sent.
 * 
 * @global type $CFG
 * @global type $DB
 * @param type $user
 * @return boolean 
 */
function local_welcome_email_usercreated($user) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // update new welcome email
    if ($dbman->table_exists('lp_welcomeemail')) {
        $record = new stdClass();
        $record->userid = $user->id;
        $record->email_sent = '0';
        $record->timemodified = time();
        $record->timesent = 0;  // always want this 0 so we can track when emails are sent

        $DB->insert_record('lp_welcomeemail', $record, false);
    }

    // force password update
    $mu_pref = $DB->get_field('user_preferences', 'id', array('userid' => $user->id, 'name' => 'auth_forcepasswordchange'));

    $record = new stdClass();
    $record->id = $mu_pref;
    $record->userid = $user->id;
    $record->name = 'auth_forcepasswordchange';
    $record->value = '1';

    // fix for #4850 http://tech.learningpool.com/issues/4850
    // check welcome hash as both md5 and whatever the default moodle has is
    $prehash = 'welcome';
    $passwordlist = array(
        'welcome',
        md5($prehash),
        hash_internal_user_password($prehash)
    );

    if (in_array($user->password, $passwordlist)) {
        if ($mu_pref != '') {
            $DB->update_record('user_preferences', $record);
        } else {
            $DB->insert_record('user_preferences', $record, false);
        }
    }
    
    return true;
}

/**
 * Used in user admin to get a count of users matching the passed criteria
 * 
 * @global type $DB
 * @param string $search
 * @param type $status
 * @return type 
 */
function get_welcomeemail_user_count($search='',$status=-1) {
    global $DB;
        
    $sql = "SELECT COUNT('x') FROM {lp_welcomeemail} we JOIN {user} u ON u.id=we.userid WHERE (1=1) ";
    $params = null;
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND CONCAT(u.lastname,', ',u.firstname,', ',u.lastname,', ',u.email) LIKE ? ";
        $params = array($search);
    }
    
    $status = (int)$status;
    switch ($status) {
        case 0:
            $sql .= " AND we.email_sent = 0 ";
            break;
        case 1:
            $sql .= " AND (we.email_sent=1 AND we.timesent>0) ";
            break;
        case 2:
            $sql .= " AND (we.email_sent=1 AND we.timesent=0) ";
            break;
        default:
            break;
    }
    
    return $DB->count_records_sql($sql, $params);
}

/**
 * Returns a list of records for users according to the passed params for display in a table 
 * 
 * @global type $DB
 * @param string $search
 * @param type $status
 * @param type $start
 * @param type $count
 * @param string $sort
 * @return string 
 */
function get_welcomeemail_users($search='',$status=-1,$start=0,$count=50,$sort='id DESC') {
    global $DB;
    
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    
    $sent = get_string('status:sent', 'local_welcome_email');
    $notsent = get_string('status:notsent', 'local_welcome_email');
    $archived = get_string('status:archived', 'local_welcome_email');
    $stopped = get_string('emailstopped', 'local_welcome_email');
    $notstopped = get_string('emailnotstopped', 'local_welcome_email');
    $yes = get_string('yes');
    $no= get_string('no');
    $params = null;
    
    $sql = "SELECT  we.id,
                    we.userid,
                    CONCAT(u.lastname,', ',u.firstname) AS fullname,
                    u.email,
                    IF(u.emailstop,'{$stopped}','{$notstopped}') AS emailstop,                    
                    IF(u.firstaccess > 0, '{$yes}', '{$no}') AS hasloggedin,
                    IF(u.deleted,'{$yes}','{$no}') AS deleted,                    
                    u.timecreated,
                    IF(we.email_sent, IF(we.timesent,'{$sent}','{$archived}'),'{$notsent}') AS status,
                    IF(we.timesent, we.timesent, ' - ') AS timesent
               FROM {lp_welcomeemail} we 
               JOIN {user} u
                    ON u.id=we.userid
              WHERE (1=1) ";    
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND CONCAT(u.lastname,', ',u.firstname,', ',u.lastname,', ',u.email) LIKE ? ";
        $params = array($search);
    }
    
    // being bit disingenuous here ...
    switch ($status) {
        case 0:
            $sql .= " AND we.email_sent = 0 ";
            break;
        case 1:
            $sql .= " AND (we.email_sent=1 AND we.timesent>0) ";
            break;
        case 2:
            $sql .= " AND (we.email_sent=1 AND we.timesent=0) ";
            break;
        default:
            break;
    }
    
    if (empty($sort)) {
        $sort = 'id DESC';
    }
    
    $sql .= " ORDER BY {$sort} ";
    $sql .= " LIMIT $start,$count ";        
    
    $records = $DB->get_records_sql($sql, $params);
    $data = array();
    $item = array();
    foreach ($records as $row) {            
        foreach ($row as $index => $val) {
            if (('timecreated' == $index || 'timesent' == $index) && intval($val)) {
                $val = userdate($val);
            }
            $item[$index] = '<span class="'.$index.'">'.$val.'</span>';
        }
        $item['checkbox'] = html_writer::checkbox('cb_'.$row->userid, null, false, '', array('id' => 'cb_'.$row->userid));
        $item ['options']= '@TODO';
        $data []= $item;
    }
    
    return $data;
}

/**
 * Cron function for this plugin - checks the daily schedule and sends welcome emails if appropriate 
 */
function local_welcome_email_cron() {
    global $DB, $CFG;
    
    $config = get_config('local_welcome_email');
    
    // check that the time to send emails is nigh
    if (empty($config->welcomeemailtime)) {
        $config->welcomeemailtime = 0;
    }
    
    $time = time();
    $hour = date('Hi');
    $limit = 2000;  // we'll only send a limited number of emails per cron - we'll only update the next run time once no more records remain
    if ((int)$hour >= (int)$config->welcomeemailtime) {
        // bully, it's time to send emails.
        $users = $DB->get_records_sql(
                    "SELECT u.*,
                            we.email_sent
                        FROM {lp_welcomeemail} we
                        JOIN {user} u
                            ON u.id=we.userid
                        WHERE we.email_sent = 0", null, 0, $limit);

        // process the result (function takes care of validating records)
        local_welcome_email_process_users($users, $config);            
    }

    // removed config for next run at aine's request DPMH - this will allow the welcome email to continue running as long as it's after the hour set
}

function local_welcome_email_process_users($users, $config) {
    global $DB, $CFG;    
    
    if (empty($users)) {
        mtrace("No users to process.");
        return;
    }
    
    $time = time();
    foreach ($users as $user) {
        $timesent = 0;
        // conditionally send email
        if ('manual' == $user->auth && !(int)$user->emailstop && (bool)$config->enablewelcomeemail) {
            mtrace("Sending to user: {$user->email} ... ");
            // cron_setup_user($user->id);
            $from = $CFG->noreplyaddress;
            $subject = get_string('default:customtemplatesubject','local_welcome_email');
            $messagehtml = local_welcome_email_get_email_text($user, $config);
            $messagetext = strip_tags($messagehtml);
            $messagehtml = nl2br($messagehtml);
            $cc = '';

            // use custom values if specified
            if (!empty($config->usecustomtemplate)) {
                if (!empty($config->customtemplatefrom)) {
                    $from = $config->customtemplatefrom;            
                }

                if (!empty($config->customtemplatesubject)) {
                    $subject = $config->customtemplatesubject;            
                }
                
                if (!empty($config->customtemplatecc)) {
                    $cc = $config->customtemplatecc;
                }
            }
            
            $timesent = $time;
            local_welcome_email_to_user($user, $from, $subject, $messagetext, $messagehtml, $cc);
        } else {            
            mtrace("Not sending to user: {$user->email} ... ");
        }
        
        // always update user as sent ... 
        $obj = $DB->get_record('lp_welcomeemail', array('userid' => $user->id));
        if ($obj) {
            $obj->timemodified = $time;
            $obj->timesent = $timesent;
            $obj->email_sent = 1;
            $DB->update_record('lp_welcomeemail',$obj);
        }
    }
}

function local_welcome_email_get_email_text($user, $config) {
    global $CFG;
    
    // get default text
    $text = get_string('default:customtemplatebody', 'local_welcome_email');
    
    // use custom value if so wished
    if (!empty($config->usecustomtemplate) && !empty($config->customtemplatebody)) {
        $text = html_entity_decode(html_entity_decode($config->customtemplatebody, ENT_COMPAT, 'UTF-8')); // double decode for double encoded <> tags
    }
    
    $site = get_site();
    
    // do replacements
    $text = str_replace('<username>', $user->username, $text);
    $text = str_replace('<firstname>', $user->firstname, $text);
    $text = str_replace('<lastname>', $user->lastname, $text);
    $text = str_replace('<sitename>', $site->fullname, $text);
    $text = str_replace('<siteurl>', $CFG->wwwroot, $text);
    
    return $text;
}

// Legacy Fixes Below ...

/**
 * Refactor old configs from DLE upgrade for this new plugin 
 */
function welcome_email_legacy_migrate_configs() {
    $oldconfig = get_config('lpscheduler_welcomeemail');
    $enableemail = get_config('lpscheduler', 'blockwelcomeemail');
    
    if (!empty($oldconfig->new_user_email_body)) {
        $oldconfig->new_user_email_body = htmlentities("Hi &lt;firstname&gt;,<br/><br/>".nl2br($oldconfig->new_user_email_body), ENT_COMPAT, 'UTF-8');
    }
    
    set_config('enablewelcomeemail', welcome_email_legacy_boolean($enableemail), 'local_welcome_email');
    set_config('welcomeemailtime', $oldconfig->schedulewelcomeemail, 'local_welcome_email');
    set_config('usecustomtemplate', welcome_email_legacy_boolean($oldconfig->enable_email_template), 'local_welcome_email');
    set_config('customtemplatecc', $oldconfig->new_user_cc, 'local_welcome_email');
    set_config('customtemplatefrom', $oldconfig->new_user_email_from, 'local_welcome_email');
    set_config('customtemplatesubject', $oldconfig->new_user_subject, 'local_welcome_email');
    set_config('customtemplatebody', $oldconfig->new_user_email_body, 'local_welcome_email');    
}

/**
 * Ahh ... this function. Want an explanation? Come up and see me sometime ... DPMH
 * 
 * @param God Only Knows $val
 * @return boolean 
 */
function welcome_email_legacy_boolean($val) {    
    if ('false' === $val || 'FALSE' === $val) {
        return false;
    }
    return (bool)$val;
}

/**
 * Returns a timestamp for the next Welcome Email run time 
 * 
 * @param string $time - the hour/minute at which the emails are to be sent
 * @param int $currenttime - optional. The time to calculate from - defaults to current time
 * @return int - a timestamp  
 */
function welcome_email_calc_next_run($time, $currenttime=null) {
    // get todays date
    if (empty($currenttime)) {
        $currenttime = time();
    }
    $date = date('Y-m-d',$currenttime);   
    $hour = substr($time,0,2);
    $minute = substr($time,2,2);

    // format the daily run time and add 1 day
    $dt = new DateTime("$date $hour:$minute");
    $dt->add(new DateInterval("P1D"));
    
    // return the timestamp expression of this time
    return $dt->getTimestamp();
}

/**
 * Replicating core moodle email_to_user function to allow adding of CC. We want the email behaviour to be 
 * identical otherwise, so this seems the most straightfoward, if wasteful, method :-\  
 * 
 * @global class $CFG
 * @global type $FULLME
 * @param type $user
 * @param type $from
 * @param type $subject
 * @param type $messagetext
 * @param type $messagehtml
 * @param type $cc
 * @param type $attachment
 * @param type $attachname
 * @param type $usetrueaddress
 * @param type $replyto
 * @param type $replytoname
 * @param type $wordwrapwidth
 * @return boolean 
 */
function local_welcome_email_to_user($user, $from, $subject, $messagetext, $messagehtml='', $cc='', $attachment='', $attachname='', $usetrueaddress=true, $replyto='', $replytoname='', $wordwrapwidth=79) {
    global $CFG, $FULLME;

    if (empty($user) || empty($user->email)) {
        $nulluser = 'User is null or has no email';
        error_log($nulluser);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$nulluser);
        }
        return false;
    }

    if (!empty($user->deleted)) {
        // do not mail deleted users
        $userdeleted = 'User is deleted';
        error_log($userdeleted);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$userdeleted);
        }
        return false;
    }

    if (!empty($CFG->noemailever)) {
        // hidden setting for development sites, set in config.php if needed
        $noemail = 'Not sending email due to noemailever config setting';
        error_log($noemail);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$noemail);
        }
        return true;
    }

    if (!empty($CFG->divertallemailsto)) {
        $subject = "[DIVERTED {$user->email}] $subject";
        $user = clone($user);
        $user->email = $CFG->divertallemailsto;
    }

    // skip mail to suspended users
    if ((isset($user->auth) && $user->auth=='nologin') or (isset($user->suspended) && $user->suspended)) {
        return true;
    }

    if (!validate_email($user->email)) {
        // we can not send emails to invalid addresses - it might create security issue or confuse the mailer
        $invalidemail = "User $user->id (".fullname($user).") email ($user->email) is invalid! Not sending.";
        error_log($invalidemail);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$invalidemail);
        }
        return false;
    }

    if (over_bounce_threshold($user)) {
        $bouncemsg = "User $user->id (".fullname($user).") is over bounce threshold! Not sending.";
        error_log($bouncemsg);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$bouncemsg);
        }
        return false;
    }

    // If the user is a remote mnet user, parse the email text for URL to the
    // wwwroot and modify the url to direct the user's browser to login at their
    // home site (identity provider - idp) before hitting the link itself
    if (is_mnet_remote_user($user)) {
        require_once($CFG->dirroot.'/mnet/lib.php');

        $jumpurl = mnet_get_idp_jump_url($user);
        $callback = partial('mnet_sso_apply_indirection', $jumpurl);

        $messagetext = preg_replace_callback("%($CFG->wwwroot[^[:space:]]*)%",
                $callback,
                $messagetext);
        $messagehtml = preg_replace_callback("%href=[\"'`]($CFG->wwwroot[\w_:\?=#&@/;.~-]*)[\"'`]%",
                $callback,
                $messagehtml);
    }
    $mail = get_mailer();

    if (!empty($mail->SMTPDebug)) {
        echo '<pre>' . "\n";
    }

    $temprecipients = array();
    $tempreplyto = array();

    $supportuser = generate_email_supportuser();

    // make up an email address for handling bounces
    if (!empty($CFG->handlebounces)) {
        $modargs = 'B'.base64_encode(pack('V',$user->id)).substr(md5($user->email),0,16);
        $mail->Sender = generate_email_processing_address(0,$modargs);
    } else {
        $mail->Sender = $supportuser->email;
    }

    if (is_string($from)) { // So we can pass whatever we want if there is need
        $mail->From     = $CFG->noreplyaddress;
        $mail->FromName = $from;
    } else if ($usetrueaddress and $from->maildisplay) {
        $mail->From     = $from->email;
        $mail->FromName = fullname($from);
    } else {
        $mail->From     = $CFG->noreplyaddress;
        $mail->FromName = fullname($from);
        if (empty($replyto)) {
            $tempreplyto[] = array($CFG->noreplyaddress, get_string('noreplyname'));
        }
    }

    if (!empty($replyto)) {
        $tempreplyto[] = array($replyto, $replytoname);
    }

    $mail->Subject = substr($subject, 0, 900);

    $temprecipients[] = array($user->email, fullname($user));

    $mail->WordWrap = $wordwrapwidth;                   // set word wrap

    if (!empty($from->customheaders)) {                 // Add custom headers
        if (is_array($from->customheaders)) {
            foreach ($from->customheaders as $customheader) {
                $mail->AddCustomHeader($customheader);
            }
        } else {
            $mail->AddCustomHeader($from->customheaders);
        }
    }

    if (!empty($from->priority)) {
        $mail->Priority = $from->priority;
    }

    if ($messagehtml && !empty($user->mailformat) && $user->mailformat == 1) { // Don't ever send HTML to users who don't want it
        $mail->IsHTML(true);
        $mail->Encoding = 'quoted-printable';           // Encoding to use
        $mail->Body    =  $messagehtml;
        $mail->AltBody =  "\n$messagetext\n";
    } else {
        $mail->IsHTML(false);
        $mail->Body =  "\n$messagetext\n";
    }

    if ($attachment && $attachname) {
        if (preg_match( "~\\.\\.~" ,$attachment )) {    // Security check for ".." in dir path
            $temprecipients[] = array($supportuser->email, fullname($supportuser, true));
            $mail->AddStringAttachment('Error in attachment.  User attempted to attach a filename with a unsafe name.', 'error.txt', '8bit', 'text/plain');
        } else {
            require_once($CFG->libdir.'/filelib.php');
            $mimetype = mimeinfo('type', $attachname);
            $mail->AddAttachment($CFG->dataroot .'/'. $attachment, $attachname, 'base64', $mimetype);
        }
    }

    // Check if the email should be sent in an other charset then the default UTF-8
    if ((!empty($CFG->sitemailcharset) || !empty($CFG->allowusermailcharset))) {

        // use the defined site mail charset or eventually the one preferred by the recipient
        $charset = $CFG->sitemailcharset;
        if (!empty($CFG->allowusermailcharset)) {
            if ($useremailcharset = get_user_preferences('mailcharset', '0', $user->id)) {
                $charset = $useremailcharset;
            }
        }

        // convert all the necessary strings if the charset is supported
        $charsets = get_list_of_charsets();
        unset($charsets['UTF-8']);
        if (in_array($charset, $charsets)) {
            $textlib = textlib_get_instance();
            $mail->CharSet  = $charset;
            $mail->FromName = $textlib->convert($mail->FromName, 'utf-8', strtolower($charset));
            $mail->Subject  = $textlib->convert($mail->Subject, 'utf-8', strtolower($charset));
            $mail->Body     = $textlib->convert($mail->Body, 'utf-8', strtolower($charset));
            $mail->AltBody  = $textlib->convert($mail->AltBody, 'utf-8', strtolower($charset));

            foreach ($temprecipients as $key => $values) {
                $temprecipients[$key][1] = $textlib->convert($values[1], 'utf-8', strtolower($charset));
            }
            foreach ($tempreplyto as $key => $values) {
                $tempreplyto[$key][1] = $textlib->convert($values[1], 'utf-8', strtolower($charset));
            }
        }
    }

    foreach ($temprecipients as $values) {
        $mail->AddAddress($values[0], $values[1]);
    }
    foreach ($tempreplyto as $values) {
        $mail->AddReplyTo($values[0], $values[1]);
    }
    
    if (!empty($cc)) {
        // send notification to cc email if exists
        $cclist = explode(',',$cc);
        if (!empty($cclist)) {            
            foreach ($cclist as $ccrec) {
                $mail->AddCC(trim($ccrec));
            }
        } 
    }

    if ($mail->Send()) {
        set_send_count($user);
        $mail->IsSMTP();                               // use SMTP directly
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return true;
    } else {
        add_to_log(SITEID, 'library', 'mailer', $FULLME, 'ERROR: '. $mail->ErrorInfo);
        if (CLI_SCRIPT) {
            mtrace('Error: mod/facetoface/lib.php facetoface_email_to_user(): '.$mail->ErrorInfo);
        }
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return false;
    }           
}

/**
 * Send email to specified user with confirmation text and activation link.
 *
 * @global object
 * @param user $user A {@link $USER} object
 * @return bool Returns true if mail was sent OK and false if there was an error.
 */
 function local_welcome_email_send_confirmation_email($user) {
    global $CFG;

    $site = get_site();
    $supportuser = generate_email_supportuser();

    $data = new stdClass();
    $data->firstname = fullname($user);
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();

    $subject = get_string('emailconfirmationsubject', '', format_string($site->fullname));

    $username = urlencode($user->username);
    $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
    $data->link  = $CFG->wwwroot .'/login/confirm.php?data='. $user->secret .'/'. $username;
    $message     = get_string('emailconfirmation', '', $data);
    $messagehtml = text_to_html(get_string('emailconfirmation', '', $data), false, false, true);

    $user->mailformat = 1;  // Always send HTML version as well

    //directly email rather than using the messaging system to ensure its not routed to a popup or jabber
    return local_welcome_email_to_user($user, $supportuser, $subject, $message, $messagehtml, get_config('local_welcome_email', 'selfregcc'));

}
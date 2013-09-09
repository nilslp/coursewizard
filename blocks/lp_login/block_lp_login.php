<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/login/block_login.php');

class block_lp_login extends block_login {
    function init() {
        $this->title = get_string('login', 'block_lp_login');
    }

    function get_content () {
        global $USER, $CFG, $SESSION;
        $wwwroot = '';
        $signup = '';

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($CFG->loginhttps)) {
            $wwwroot = $CFG->wwwroot;
        } else {
            // This actually is not so secure ;-), 'cause we're
            // in unencrypted connection...
            $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
        }

        if (!empty($CFG->registerauth)) {
            $authplugin = get_auth_plugin($CFG->registerauth);
            if ($authplugin->can_signup()) {
                $signup = $wwwroot . '/login/signup.php';
            }
        }

        if (empty($CFG->xmlstrictheaders) and !empty($CFG->loginpasswordautocomplete)) {
            $autocomplete = 'autocomplete="off"';
        } else {
            $autocomplete = '';
        }

        $username = get_moodle_cookie();

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        if (!isloggedin() or isguestuser()) {   // Show the block

            $this->content->text .= "\n".'<form class="loginform" id="login" method="post" action="'.get_login_url().'" '.$autocomplete.'>';

            $this->content->text .= '<div class="c1 fld username"><label for="login_username">'.get_string('username').'</label>';
            $this->content->text .= '<input type="text" name="username" id="login_username" value="'.s($username).'" /></div>';

            $this->content->text .= '<div class="c1 fld password"><label for="login_password">'.get_string('password').'</label>';

            $this->content->text .= '<input type="password" name="password" id="login_password" value="" '.$autocomplete.' /></div>';

            if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) {
                $checked = $username ? 'checked="checked"' : '';
                $this->content->text .= '<div class="c1 rememberusername"><input type="checkbox" name="rememberusername" id="rememberusername" value="1" '.$checked.'/>';
                $this->content->text .= ' <label for="rememberusername">'.get_string('rememberusername', 'admin').'</label></div>';
            }

            $this->content->text .= '<div class="c1 btn"><input type="submit" value="'.get_string('login').'" /></div>';

            $this->content->text .= "</form>\n";
            
            if (get_config('block_lp_login', 'showforgottenpasswordlink')) {
                $this->content->footer .= '<div><a href="'.$wwwroot.'/login/forgot_password.php">'.get_string('forgotaccount').'</a></div>';
            }
            
            // conditionally add guest login
            if (($CFG->guestloginbutton and !isguestuser())) {
                $this->content->footer .= '<div class="guest">
                        <form action="'.$CFG->httpswwwroot.'/login/index.php" method="post" id="guestlogin">
                            <input type="hidden" name="username" value="guest" />
                            <input type="hidden" name="password" value="guest" />
                            <button type="submit" title="'.get_string("someallowguest").'"><span>'.get_string("loginguest").'</span></button>
                        </form>
                    </div>';
            }
            
            // conditionally add self reg
            if ($CFG->registerauth == 'email') {
                $this->content->footer .= '<div class="register">
                        <form action="'.$CFG->httpswwwroot.'/login/signup.php" method="get">
                            <button type="submit"><span>'.get_string("startsignup", 'theme_enterprise').'</span></button>
                        </form>
                    </div>';
            }
            
            // conditionally add third party
            if (strpos($CFG->auth, 'thirdparty') !== false) {
                $this->content->footer .= '<div class="third-party-reg">
                    <form action="'.$CFG->httpswwwroot.'/auth/thirdparty/signup/signup.php" method="get">
                        <button type="submit"><span>'.get_string('third_party_reg', 'theme_enterprise').'</span></button>
                    </form>
                </div>';
            }
            
        }
        
        return $this->content;
    }
    
    function instance_allow_multiple() {
        return false;
    }
    
    /**
     * Does the plugin have a settings page?
     * @return boolean
     */
    function has_config() {
        return true;
    }
}



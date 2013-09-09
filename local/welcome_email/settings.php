<?php 
/**
 * Adding new menu item to the settings > site administration > Server >  tree.
 */

defined('MOODLE_INTERNAL') || die;

    
$ADMIN->add('root',
    new admin_category('welcomeemail',
    get_string('menuitem','local_welcome_email'))
);

$ADMIN->add('welcomeemail', new admin_externalpage('welcomeemailadmin',
        get_string('customemailsettings', 'local_welcome_email'),
        new moodle_url('/local/welcome_email/customemail.php'),
        array('local/welcome_email:config')));

$ADMIN->add('welcomeemail', new admin_externalpage('selfregemailadmin',
        get_string('selfregsettings', 'local_welcome_email'),
        new moodle_url('/local/welcome_email/selfreg.php'),
        array('local/welcome_email:config')));

$ADMIN->add('welcomeemail', new admin_externalpage('welcomeemailindex',
        get_string('viewsent', 'local_welcome_email'),
        new moodle_url('/local/welcome_email/index.php'),
        array('local/welcome_email:config')));
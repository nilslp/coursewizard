<?php
/**
 * Adding new menu item to the settings > site administration > Server >  tree.
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page

    // needs this condition or there is error on login page
    $ADMIN->add('root',
        new admin_category('dlelegacytools',
        get_string('menuitem','local_dlelegacytools'))
    );

    $ADMIN->add('dlelegacytools', new admin_externalpage('dlescormsettings',
            get_string('scormsettings', 'local_dlelegacytools'),
            new moodle_url('/local/dlelegacytools/scorm/settings.php')));


    $ADMIN->add('dlelegacytools', new admin_externalpage('undeluserssettings',
        get_string('undeleteusers', 'local_dlelegacytools'),
        new moodle_url('/local/dlelegacytools/undelete/settings.php')));


    $ADMIN->add('dlelegacytools', new admin_externalpage('dlelafsettings',
            get_string('lafsettings', 'local_dlelegacytools'),
            new moodle_url('/local/dlelegacytools/interface/settings.php')));

    /*$ADMIN->add('dlelegacytools', new admin_externalpage('lpscheduler',
            get_string('lpscheduler_menuitem', 'local_dlelegacytools'),
            new moodle_url('/local/dlelegacytools/lpscheduler/index.php')));*/

    /** Scheduler Menu Item **/
    $ADMIN->add('dlelegacytools',
        new admin_category('scheduler',
        get_string('lpscheduler_menuitem','local_dlelegacytools'))
    );

    $ADMIN->add('scheduler', new admin_externalpage('lpscheduler',
            get_string('lpscheduler_submenuitem', 'local_dlelegacytools'),
            new moodle_url('/local/dlelegacytools/lpscheduler/index.php')));

    /**
     * DPMH - plugin is deprecated. See local/welcome_email instead.
     * 
    $ADMIN->add('scheduler', new admin_externalpage('welcomeemail',
            get_string('welcomeemail_menu_name', 'local_dlelegacytools'),
            new moodle_url('/local/dlelegacytools/lpscheduler/welcomeemail/index.php')));
     */

}
 
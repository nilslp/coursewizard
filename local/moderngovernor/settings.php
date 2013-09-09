<?php 
/**
 * Adding new menu item to the settings > site administration > Server >  tree.
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

if ($hassiteconfig && false !== strpos($CFG->dbname, 'moodle_mg')) { // not my finest work ... DPMH
    
    // needs this condition or there is error on login page
    $ADMIN->add('root',
        new admin_category('moderngovernorsettings',
        get_string('menuitem','local_moderngovernor'))
    );
    
    $ADMIN->add('moderngovernorsettings', new admin_externalpage('moderngovernorindex',
            get_string('settings', 'local_moderngovernor'),
            new moodle_url('/local/moderngovernor/index.php')));
        
    $ADMIN->add('moderngovernorsettings', new admin_externalpage('moderngovernoradmin',
            get_string('admin', 'local_moderngovernor'),
            new moodle_url('/local/moderngovernor/admin.php')));
        
    $ADMIN->add('moderngovernorsettings', new admin_externalpage('moderngovernorusers',
            get_string('adminusers', 'local_moderngovernor'),
            new moodle_url('/local/moderngovernor/users.php')));
    
    // conditionally add demo user admin form
    if ((int)  get_config('local_moderngovernor', 'enabledemousers')) {
        $ADMIN->add('moderngovernorsettings', new admin_externalpage('moderngovernoraccountadmin',
                get_string('adddemoaccount', 'local_moderngovernor'),
                new moodle_url('/local/moderngovernor/addaccount.php')));
    }
}

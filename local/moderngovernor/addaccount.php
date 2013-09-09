<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'moderngovernoraccountadmin' );

$renderer = $PAGE->get_renderer('local_moderngovernor');

$renderer->print_demo_account_admin();

<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'welcomeemailindex' );

$renderer = $PAGE->get_renderer('local_welcome_email');

$renderer->print_user_table();

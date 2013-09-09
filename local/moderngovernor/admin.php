<?php

require_once('../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once( $CFG->libdir.'/adminlib.php' );
require_once( $CFG->dirroot.'/blocks/lp_hierarchy/lib.php' );
require_once( 'lib.php' );

admin_externalpage_setup( 'moderngovernoradmin' );

$renderer = $PAGE->get_renderer('local_moderngovernor');

$renderer->print_school_admin();

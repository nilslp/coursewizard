<?php

/**
 * Page to execute report builder cron on a particular activity group and display results
 */

//TODO require admin permissions

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/cron.php');

$group = optional_param('group', 0, PARAM_INT);
// TODO
//if(!confirm_sesskey()) {
//    print_error('confirmsesskeybad','error');
//}
print '<pre>';
print "Starting cron...\n";
reportbuilder_cron($group);
print "\n...cron complete.\n";
print '</pre>';

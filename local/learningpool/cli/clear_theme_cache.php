<?php
/**
 *  CLI Script to clear only the theme cache ...
 */

define('CLI_SCRIPT',true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;
require_once($CFG->libdir.'/outputlib.php');
theme_reset_all_caches();

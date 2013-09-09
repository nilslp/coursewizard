<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle_lp_coursewizard';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbsocket' => 0,
);

$CFG->wwwroot   = 'http://coursewizard.nils.hi';
$CFG->dataroot  = '/www2/sites/coursewizard.nils.hi/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

$CFG->passwordsaltmain = '4X)U&5#z{{P6jAh!)MV&`.MZI8';

require_once(dirname(__FILE__) . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!

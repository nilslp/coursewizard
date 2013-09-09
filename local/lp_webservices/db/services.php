<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service local plugin defines external functions and service definitions.
 *
 * @package    local
 * @subpackage lp_webservices
 * @copyright  2013 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Functions exposed by this webservice
$functions = array(
        'local_learningpool_get_completion_status' => array(
                'classname'   => 'local_lp_webservices_external',
                'methodname'  => 'get_completion_status',
                'classpath'   => 'local/lp_webservices/externallib.php',
                'description' => 'Gets the completion status for a given user on a given course',
                'type'        => 'read',
        )
);

// Pre built "Learning Pool" webservices
$services = array(
        'Learning Pool' => array(
                'functions' => array (
                    'local_learningpool_get_completion_status'
                    ),
                'shortname' => 'learningpool_webservice',
                'restrictedusers' => 0,
                'downloadfiles' => 1,
                'enabled'=>1,
        )
);

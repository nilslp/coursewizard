<?php
// This file is part of Moodle - http://moodle.org/
//
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

global $PAGE;

if ($hassiteconfig) {
    $ADMIN->add('learningpooladmin',
        new admin_externalpage('lp_webservices_configure',
            get_string('configurewebservices','local_lp_webservices'),
            "$CFG->wwwroot/local/lp_webservices/configure.php",
            array('local/lp_webservices:config')
        )
    );
}
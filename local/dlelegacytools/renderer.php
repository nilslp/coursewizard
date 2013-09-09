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

/**
 * Defines the renderer for the question engine upgrade helper plugin.
 *
 * @package    local dleletools
 * @subpackage lpscheduler
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ("$CFG->dirroot/lib/formslib.php");
/**
 * Renderer for the question engine upgrade helper plugin.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_dlelegacytools_renderer extends plugin_renderer_base {
    
    /**
     * A cache of strings
     * @var stdClass
    */
    protected $strings;

    /**
         * Override the constructor so that we can initialise the string cache
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        $this->strings = new stdClass;
        parent::__construct($page, $target);
    }

    
	
    
    /**
     * Render a page that is just a simple message.
     * @param string $message the message to display.
     * @return string html to output.
     */
    public function simple_message_page($message) {
    	 global $CFG, $USER, $DB, $OUTPUT;
        $output = '';
        $output .= $this->header();
        $output .= $this->heading($message);
        $output .= $this->back_to_index();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Render a link in a div, such as the 'Back to plugin main page' link.
     * @param $url the link URL.
     * @param $text the link text.
     * @return string html to output.
     */
    public function end_of_page_link($url, $text) {
        return html_writer::tag('div', html_writer::link($url ,$text),array('class' => 'mdl-align'));
    }

    /**
     * Output a link back to the plugin index page.
     * @return string html to output.
     */
    public function back_to_index() {
        return $this->end_of_page_link('/local/lpscheduler/index.php',get_string('backtoindex', 'local_dlelegacytools'));
    }
	
}
	


?>

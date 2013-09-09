<?php
/**
 * Block to allow the user to enter a license key
 * @copyright Learning Pool
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package redeem_license
 */

class block_redeem_license extends block_base {
    function init() {
        $this->title   = get_string('pluginname', 'block_redeem_license');
        $this->version = 2013040800;
    }

    function get_content() {
        global $CFG;
        
        // Prevent this code firing more than once
        if ($this->content != null) {
            return $this->content;
        } else {
            $this->content =  new stdClass;
            $this->content->text = '';
            
            // Only logged users can redeem a license - no guests
            if(isloggedin() && !isguestuser()) {
                // Define the HTML for the simple form
                $output = '';
                $output .= html_writer::tag('div', get_string('blocktext', 'block_redeem_license'));
                $output .= html_writer::start_tag('form', array('action' => $CFG->wwwroot .'/blocks/redeem_license/redeem.php', 'method' => 'post'));
                $output .= html_writer::empty_tag('input', array('id' => 'license_code', 'name' => 'license_code', 'type' => 'text', 'placeholder' => get_string('placeholder', 'block_redeem_license'), 'autocomplete' => 'off'));
                $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('redeem', 'block_redeem_license')));
                $output .= html_writer::end_tag('form');

                $this->content->text .= $output;
            }
            
            return $this->content;
        }
    }
    
    function instance_allow_config() {
        return true;
    }

    function instance_allow_multiple() {
        return false;
    }
}
?>

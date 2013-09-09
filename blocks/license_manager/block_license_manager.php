<?php
/**
 * Block to display a link to the License Manager of the shopping_basket plugin
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package license_manager
 */

class block_license_manager extends block_base {
    function init() {
        $this->title   = get_string('pluginname', 'block_license_manager');
        $this->version = 2013040500;
    }

    function get_content() {
        // Prevent this code firing more than once
        if ($this->content != null) {
            return $this->content;
        } else {
            $this->content =  new stdClass;
            $this->content->text = '';
            
            // Display for logged in users only, no guests
            if(isloggedin() && !isguestuser()) {
                global $CFG;
                require_once $CFG->dirroot . '/blocks/shopping_basket/lib.php';
                $this->content->text .= display_license_manager_link();
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

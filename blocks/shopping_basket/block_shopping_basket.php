<?php
/**
 *
 * @copyright Learning Pool
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */

class block_shopping_basket extends block_base {
    function init() {
        $this->title   = get_string('pluginname', 'block_shopping_basket');
        $this->version = 2013031100;
    }

    function get_content() {
        // Prevent this code firing more than once
        if ($this->content != null) {
            return $this->content;
        }
        else {
            global $CFG;
            require_once $CFG->dirroot . '/blocks/shopping_basket/cart/shopping_cart.php';
        
            $this->content =  new stdClass;
            $this->content->text = html_writer::tag('div', ShoppingCart::display_basket(), array('id' => 'basket_container'));
            $context = get_context_instance(CONTEXT_SYSTEM);
            if (has_capability('block/shopping_basket:managesettings', $context)) {
                // Display a link to the shopping basket settings page
                $this->content->text .= html_writer::tag('div', html_writer::link(get_settings_url(),
                    get_string('settingbasketsettings', 'block_shopping_basket')));
            }
            return $this->content;
        }
    }

     function get_required_javascript() {
        global $PAGE, $USER;

        $jsconfig = array(
            'name' => 'block_shopping_basket',
            'fullpath' => '/blocks/shopping_basket/block_shopping_basket.js',
            'requires' => array('base','node','selector-css3','event', 'event-focus','io', 'json-parse', 'panel'),
            'strings' => array(
                            array('licensepurchase_help_title', 'block_shopping_basket'),
                            array('licensepurchase_help_body1', 'block_shopping_basket'),
                            array('licensepurchase_help_body2', 'block_shopping_basket'),
                            array('licensepurchase_help_body3', 'block_shopping_basket'),
                            array('pleaseenterponumber', 'block_shopping_basket')
                )
        );

        // Check if this is on the course enrolment page
        $url = $_SERVER["REQUEST_URI"];        
        $pos = strpos($url, '/enrol/index.php');
        $in_enrolment = ($pos === false ? false : true);
        
        $PAGE->requires->js_init_call('M.block_shopping_basket.init', array($USER->sesskey, false, $in_enrolment), true, $jsconfig);
    }

    function instance_allow_config() {
        return true;
    }

    function instance_allow_multiple() {
        return false;
    }
    
    function cron() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/shopping_basket/cron.php');
        
        $this->trace('cron started at '. date('H:i:s'));
        try {
            shopping_basket_cron();
        } catch (Exception $e) {
            $this->trace('cron failed with an exception:');
            $this->trace($e->getMessage());
        }
        $this->trace('cron finished at ' . date('H:i:s'));
        // Must return true?
        return true;
    }
    
    /**
     * Helper function to print our messages consistently
     */
    function trace($msg) {
        mtrace('shopping_basket: ' . $msg);    
    }
    
    /**
     * Does the plugin have a settings page?
     * @return boolean
     */
    function has_config() {
        return true;
    }
}
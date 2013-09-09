<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

/**
 * Take a given license code, works out the product associated and enrols the
 * current user on to any courses for the specified duration
 * @param string $licensekey Unique identifier for license
 */
function redeem_license($licensekey) {
    global $USER,$DB;
    
    $license = $DB->get_record('shopping_basket_license', array('licensekey' => $licensekey, 'userid' => NULL), '*', MUST_EXIST);
    
    if ($license) {
        // Get the product
        $product = get_product($license->productid);
        
        // Calculate the end date of the enrolment (if it has not been defined)
        if (!isset($license->enddate)) {
            $license->startdate = time();
            $license->enddate = $license->startdate + $product->enrolperiod;
        }
        
        if(get_config('enrol_premium','autoenrol')==1) {
            // Enrol the user on each course
            foreach ($product->courses as $course) {
                enrol_premium_user($course, $USER->id, $license->startdate, $license->enddate);
            }
        }

        // Indicate that the current user has redeemed the license
        $license->userid = $USER->id;
        
        $DB->update_record('shopping_basket_license', $license);
    }
}

/**
 * Checks that a given license exists, has not ben used, and has not expired
 * @param string $licensekey Unique identifier of the license
 * @return string A validatiion message (if required)
 */
function validate_license($licensekey) {
    global $DB;
    
    if (empty($licensekey)) {
        return get_string('err:nolicencekey', 'block_redeem_license');
    }
    
    $license = $DB->get_record('shopping_basket_license', array('licensekey' => $licensekey));
    
    if (!$license) {
        // License does not exist
        return get_string('err:nonexistant', 'block_redeem_license');
    } 
    
    if ($license->userid !== NULL) {
        // License is already in use
        return get_string('err:inuse', 'block_redeem_license');
    }
    
    if (isset($license->enddate)) {
        if ($license->enddate < time()) {
            // License has expired
            return get_string('err:expired', 'block_redeem_license');
        }
    }
    
    return '';
}

/**
 * Takes a license key and returns a product object
 * @param string $licensekey Unique identifier of license
 * @return product
 */
function get_product_from_license($licensekey) {
    global $DB;
    
    $license = $DB->get_record('shopping_basket_license', array('licensekey' => $licensekey), '*', MUST_EXIST);
    
    $product = get_product($license->productid);
    
    return $product;
}

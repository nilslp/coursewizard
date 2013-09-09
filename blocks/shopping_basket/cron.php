<?php
/**
 * Cron Tasks for Shopping Basket
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */

require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

/**
 * Run the cron functions required by shopping basket
 * @return boolean true if completes successfully, false otherwise
 */
function shopping_basket_cron() {
    global $DB;
    $timelastrun = get_config('block_shopping_basket', 'lastcron');
    
    send_unfulfilled_order_alerts();
    send_unpaid_order_alerts();

    // Assign any new courses which have been added to a product to users
    // who have purchased a corresponding license
    if (get_config('enrol_premium', 'autoenrol')) {
        // Get a list of products which have updated recently
        $products = $DB->get_records_sql( 
            "SELECT *
            FROM {shopping_basket_product}
            WHERE deleted = 0 AND timemodified >= $timelastrun");

        foreach ($products as $product) {
            if (!$product->hascategory) {
                // Get any new courses for this product
                $product_courses = $DB->get_records_sql("
                    SELECT *, course AS courseid
                    FROM {shopping_basket_prod_course}
                    WHERE product = :product AND timemodified >= :time",
                    array('product' => $product->id, 'time' => $timelastrun));
            }
            else {
                // Not an ideal solution right now but we need to get and check
                // enrollments for all the courses in a category
                $product_courses = get_product($product->id)->courses;
            }
            
            // Get any licenses which use this product
            $licenses = $DB->get_records_sql(
                "SELECT * 
                FROM {shopping_basket_license}
                WHERE productid = :productid 
                    AND userid IS NOT NULL 
                    AND enddate > :time", array('productid' => $product->id, 'time' => $timelastrun));

            foreach ($licenses as $license) {
                // Check the user is enrolled on any new courses
                foreach ($product_courses as $productcourse) {
                    $context = get_context_instance(CONTEXT_COURSE, $productcourse->courseid);
                    if (!is_enrolled($context, $license->userid)) {
                        // Course must be passed in as a class
                        $course = new stdClass();
                        $course->id = $productcourse->courseid;

                        enrol_premium_user($course, $license->userid, time(), $license->enddate);
                    }
                }
            }
        }
    }
    
    enrol_expire_notifications();
    
    set_config('lastcron', time(), 'block_shopping_basket');
    
    return true;
}

/**
 * Find orders that have not been fulfilled since the last cron run
 * @global type $DB
 * @return boolean
 */
function send_unfulfilled_order_alerts() {
    global $DB;
    
    $now = time();
    $lastcron = (int)get_config('block_shopping_basket', 'lastcron');
    
    // Grab orders that have been paid for, but not fulfilled
    $sql = "SELECT
                  sbo.id as orderid
            FROM  {shopping_basket_order} sbo
            WHERE sbo.fulfilled = 0
            AND   sbo.payment_status = :complete
            AND   sbo.timemodified >= :lastcron
            AND   sbo.timemodified <  :now
            ";

    if(!$unfulfilled_orders = $DB->get_records_sql($sql,array('complete'=>PAYMENT_STATUS_COMPLETED,'lastcron'=>$lastcron,'now'=>$now))) {
        $unfulfilled_orders = array();
    }
    
    mtrace('Processing ' . count($unfulfilled_orders) . ' unfulfilled orders found for time period: ' . userdate($lastcron) . ' - ' . userdate($now));
    
    foreach ( $unfulfilled_orders as $unfulfilled_order ) {
        // Check order fulfillment
        $order = get_order($unfulfilled_order->orderid);
        $check = check_order_fulfilled($order);
        if (!$check->fulfilled) {
            // Send mail to admin ...
            $subject = 'Order ID: ' . $order->id . ' unfulfilled:\n';
            $message = '';
            foreach ( $check->errors as $e ) {
                $message .= $e . '\n';
            }
            send_alert_mail($subject, $message);
        } else {
            // Order has no issues, but is marked as unfulfilled
            // Update?
            /*$o = new stdClass();
            $o->id = $order->id;
            $o->fulfilled = 1;
            $o->timemodified = time();
            $DB->update_record('shopping_basket_order', $o);*/
        }
    }
    
    return true;
}

/**
 * Find orders that have not been paid for since the last cron run
 * @global type $DB
 * @return boolean
 */
function send_unpaid_order_alerts() {
    global $DB;
    
    $now = time();
    $lastcron = (int)get_config('block_shopping_basket', 'lastcron');
    
    // Grab orders that have not been paid for
    $sql = "SELECT
                  sbo.id as orderid
            FROM  {shopping_basket_order} sbo
            WHERE sbo.fulfilled = 0
            AND   ( sbo.payment_status != :complete OR sbo.payment_status IS NULL )
            AND   sbo.timemodified >= :lastcron
            AND   sbo.timemodified <  :now
            ";

    if(!$unpaid_orders = $DB->get_records_sql($sql,array('complete'=>PAYMENT_STATUS_COMPLETED,'lastcron'=>$lastcron,'now'=>$now))) {
        $unpaid_orders = array();
    }
    
    mtrace('Processing ' . count($unpaid_orders) . ' unpaid orders found for time period: ' . userdate($lastcron) . ' - ' . userdate($now));
    
    foreach ( $unpaid_orders as $unpaid_order ) {
        // Check order fulfillment
        $order = get_order($unpaid_order->orderid);
        // Send mail to admin ...
        $subject = 'Order ID: ' . $order->id . ' unpaid.';
        $message = 'Order will not be processed until the User has completed payment.';
        send_alert_mail($subject, $message);
    }
    
    return true;
}

/**
 * Send a mail to the site admin, cc'ing the alert email address
 * @param type $subject
 * @param type $data
 */
function send_alert_mail($subject, $message) {
    global $CFG, $DB;
    $admin = get_admin();
    $site = get_site();
    $fromaddress = $CFG->noreplyaddress;
    $messagetext = "$site->fullname: \n\n$subject\n\n$message\n\n";
    
    $sent = shopping_basket_email_to_user($admin, $fromaddress, $subject, $messagetext, true);
    
    if(!$sent) {
        mtrace('Alert Mail to admin failed');
    }
    return $sent;
}

/**
* Notify users about purchased enrolments that are going to expire soon
* @return void
*/
function enrol_expire_notifications() {
    global $CFG, $SITE, $DB;
    
    // If expiry reminders are disabled, we've nothing to do here
    if (get_config('block_shopping_basket','enableexpiryemail')==0) {
        return;
    }
    
    if (!isset($CFG->shopping_basket_lastexpirynotify)) {
        set_config('shopping_basket_lastexpirynotify', 0);
    }

    // Notify once a day only
    if ($CFG->shopping_basket_lastexpirynotify == date('Ymd')) {
        return;
    }
    
    $threshold = get_config('block_shopping_basket', 'expirythreshold');
    $x_days_time = time() + $threshold;
    $beginning_of_day = strtotime('midnight', $x_days_time);
    $end_of_day = strtotime('tomorrow', $beginning_of_day) - 1;
    
    $sql = " SELECT l.id, l.userid, p.fullname AS productname
             FROM   {shopping_basket_license} l
             INNER JOIN {shopping_basket_product} p ON p.id = l.productid
             WHERE  l.userid IS NOT NULL
                AND l.enddate >= :begin
                AND l.enddate <= :end
             GROUP BY l.userid, l.productid
             ORDER BY l.enddate ASC ";
    
    $params = array(
        'begin' => $beginning_of_day,
        'end' => $end_of_day,
    );
    
    $expiring = $DB->get_records_sql($sql, $params);
    
    mtrace('Found '.count($expiring).' license(s) expiring between '.userdate($beginning_of_day).' and ' . userdate($end_of_day));
    
    $admin = get_admin();
    $custommessage = get_config('block_shopping_basket', 'customexpiryemail');
    $mails = 0;
    foreach ($expiring as $e) {
        if ($user = $DB->get_record('user', array('id' => $e->userid))) {
            $a = new object();
            $a->studentstr = fullname($user, true);
            $a->threshold = $threshold/86400;
            $a->product = $e->productname;

            if (trim($custommessage) == '') {
                $strexpirynotifyemail = get_string('defaultexpiryemail', 'block_shopping_basket', $a);
                $strexpirynotifyemailhtml = text_to_html(get_string('defaultexpiryemail', 'block_shopping_basket', $a), false, false, true);
            } else {
                $custommessage = html_entity_decode($custommessage);
                $custommessage = str_replace('{$a->studentstr}', $a->studentstr, $custommessage);
                $custommessage = str_replace('{$a->threshold}', $a->threshold, $custommessage);
                $custommessage = str_replace('{$a->product}', $a->product, $custommessage);
                $strexpirynotifyemail = $custommessage;
                $strexpirynotifyemailhtml = text_to_html($custommessage, false, false, true);
            }

            $strexpirynotify = get_string('expiryemailsubject', 'block_shopping_basket', $a);

            $sent = email_to_user($user, $admin, format_string($SITE->fullname) .' '. $strexpirynotify, strip_tags($strexpirynotifyemail), $strexpirynotifyemailhtml);

            if ($sent) {
                $mails++;
            } else {
                mtrace('Purchased enrolment expiry notice to '.$user->email .' failed');
            }
        } else {
            mtrace('User could not be found - ID:'.$e->userid);
        }
    }
    
    mtrace($mails.' expiration notification(s) sent');
    
    set_config('shopping_basket_lastexpirynotify', date('Ymd'));
    
    return true;
}

?>

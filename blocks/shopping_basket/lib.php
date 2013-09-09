<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once "$CFG->dirroot/lib/formslib.php";

define('DISCOUNT_TYPE_PERCENTAGE', 1);
define('DISCOUNT_TYPE_FIXED_ORDER', 2);
define('DISCOUNT_TYPE_FIXED_ITEM', 3);

define('VOUCHER_CRITERIA_EQUAL', 0);
define('VOUCHER_CRITERIA_NOT_EQUAL', 1);
define('VOUCHER_CRITERIA_BEGINS_WITH', 2);
define('VOUCHER_CRITERIA_ENDS_WITH', 3);

define('TIER_TYPE_QUANTITY', 1);
define('TIER_TYPE_VALUE', 2);

define('TIER_CRITERIA_PERCENTAGE', 1);
define('TIER_CRITERIA_FIXED_AMOUNT', 2);

define('PAYMENT_STATUS_ABORTED', 'Aborted');
define('PAYMENT_STATUS_AUTHENTICATED', 'Authenticated');
define('PAYMENT_STATUS_COMPLETED', 'Completed');
define('PAYMENT_STATUS_DECLINED', 'Declined');
define('PAYMENT_STATUS_ERROR', 'Error');
define('PAYMENT_STATUS_INVALID', 'Invalid');
define('PAYMENT_STATUS_MALFORMED', 'Malformed');
define('PAYMENT_STATUS_PENDING', 'Pending');
define('PAYMENT_STATUS_REGISTERED', 'Registered');
define('PAYMENT_STATUS_REJECTED', 'Rejected');
define('PAYMENT_STATUS_UNKNOWN', 'Unknown');

define('PAYMENT_PROVIDER_PAYPAL', 1);
define('PAYMENT_PROVIDER_SAGEPAY', 2);
/**
 * Returns a list of products
 * @return type
 */
function get_products() {
    global $DB;
    
    $products = $DB->get_records('shopping_basket_product', array('deleted' => 0), 'itemcode ASC');
    
    return $products;
}

/**
 * Gets a specific product instance
 * @global moodle_database $DB
 * @param int $id Unique identifier of the product
 */
function get_product($id) {
    global $DB;
    
    // Retrieve the product
    $product = $DB->get_record('shopping_basket_product', array('id' => $id, 'deleted' => 0), '*', MUST_EXIST);
    $courses = array();
    $categories = array();
    $course_list = array();
    $category_list = array();
    
    if ($product->hascategory == 1) {
        $categories = $DB->get_records_sql("SELECT cc.id, cc.name
                    FROM {shopping_basket_prod_cat} pc
                    INNER JOIN {course_categories} cc ON cc.id = pc.category
                    WHERE pc.product = :productid ", array('productid' => $id));
        
        // Only proceed if categories have been assigned.
        // Otherwise, all courses will be returned
        if (count($categories)) {
            foreach ($categories as $category) {
                $category_list[] = $category->id;
            }

            $categoryclause = array();
            $whereclause = '';

            foreach ($categories as $category) {
                $categoryclause[] = "(cc.id = {$category->id} OR cc.path LIKE '%/{$category->id}/%') ";
            }

            if (count($categoryclause)) {
                $whereclause = 'WHERE ' . join($categoryclause, ' OR ');
            }

            // Retrieve any courses associated with the product category
            // (or children of the category)
            $courses = $DB->get_records_sql(
                    "SELECT c.id, c.id AS courseid, c.fullname
                    FROM {course} c
                    INNER JOIN {course_categories} cc ON c.category = cc.id 
                    $whereclause 
                    ORDER BY c.fullname ASC");
        }
    } else {
        // Retrieve any courses associated with the product
        $courses = $DB->get_records_sql("SELECT c.id, c.id AS courseid, c.fullname
                    FROM {shopping_basket_prod_course} p
                    INNER JOIN {course} c ON p.course = c.id
                    WHERE p.product = :productid 
                    ORDER BY c.fullname ASC", array('productid' => $id));
    
        foreach ($courses as $course) {
            $course_list[] = $course->id;
        }
    }
    
    $product->courses = $courses;
    $product->courseidlist = join(',', $course_list);
    $product->categories = $categories;
    $product->categoryidlist = join(',', $category_list);
    
    return $product;
}

/**
 * Checks a product code is unique on the shopping_basket_product table
 * @param string $itemcode
 * @return boolean
 */
function is_itemcode_unique($itemcode) {
    global $DB;
    
    $itemcode = ltrim(rtrim($itemcode));
    
    $records = $DB->get_records('shopping_basket_product', array('itemcode' => $itemcode));
        
    if ($records) {
        return false;
    }
    else {
        return true;
    }
}

/**
 * Checks a given voucher code is unique on the shopping_bakset_voucher table
 * @param string $discountcode
 * @return boolean
 */
function is_discountcode_unique($discountcode) {
    global $DB;
    
    $discountcode = ltrim(rtrim($discountcode));
    
    $records = $DB->get_records('shopping_basket_voucher', array('discountcode' => $discountcode));
        
    if ($records) {
        return false;
    }
    else {
        return true;
    }
}

/**
 * Returns a HTML table (required to keep client and server-side in sync)
 * @global type $CFG
 * @param array $courses
 * @return string HTML table
 */
function get_course_table_html($courses) {
    global $CFG;
    
    $table = new html_table();
    // This ID is required for the associated javascipt
    $table->id = 'course_table';

    $data = array();

    // Render the course
    foreach ($courses as $course) {
        $cells = array();

        $cells[] = new html_table_cell($course->fullname);
        $cells[] = new html_table_cell(html_writer::link('#', html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/blocks/shopping_basket/pix/delete.gif')), 
                array('id' => 'course_' . $course->id, 'title' => get_string('clicktoremove', 'block_shopping_basket'))));
        
        $row = new html_table_row($cells);    
        $row->id = 'course_row_' . $course->id;

        $data[] = $row;
    }
    
    $table->data = $data;
            
    return html_writer::table($table);
}

/**
 * Returns a HTML table (required to keep client and server-side in sync)
 * @global type $CFG
 * @param array $categories
 * @return string HTML table
 */
function get_category_table_html($categories) {
    global $CFG;
    
    $table = new html_table();
    // This ID is required for the associated javascipt
    $table->id = 'category_table';

    $data = array();

    // Render the course
    foreach ($categories as $category) {
        $cells = array();

        $cells[] = new html_table_cell($category->name);
        $cells[] = new html_table_cell(html_writer::link('#', html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/blocks/shopping_basket/pix/delete.gif')), 
                array('id' => 'category_' . $category->id, 'title' => get_string('clicktoremove', 'block_shopping_basket'))));
        
        $row = new html_table_row($cells);
        $row->id = 'category_row_' . $category->id;

        $data[] = $row;
    }
    
    $table->data = $data;
            
    return html_writer::table($table);
}

/**
 * Inserts a new product and courses
 * @global moodle_database $DB
 * @param stdClass $product
 */
function add_product($product) {
    global $DB, $USER;

    $courses = explode(',', $product->courseidlist);
    $categories = explode(',', $product->categoryidlist);
    $product = clean_product_data($product);
    
    $time = time();
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        // Add the product
        $productid = $DB->insert_record('shopping_basket_product', $product, true);
        if($product->hascategory == 0) {
            // Associate the courses
            foreach ($courses as $course) {
                $product_course = new stdClass();
                $product_course->product = $productid;
                $product_course->course = $course;
                $product_course->timemodified = $time;
                $product_course->modifierid = $USER->id;

                $DB->insert_record('shopping_basket_prod_course', $product_course);
            }
        } else {
            // Associate the categories
            foreach ($categories as $category) {
                $product_category = new stdClass();
                $product_category->product = $productid;
                $product_category->category = $category;
                $product_category->timemodified = $time;
                $product_category->modifierid = $USER->id;

                $DB->insert_record('shopping_basket_prod_cat', $product_category);
            }
        }
        $transaction->allow_commit();
    }
    catch (Exception $e) {
        $transaction->rollback($e);
    }
}

function delete_product($id) {
    global $USER, $DB;
    
    $product = get_product($id);
    
    $product->deleted = 1;
    $product->modifierid = $USER->id;
    $product->timemodified = time();
    
    
    $DB->update_record('shopping_basket_product', $product);
}

/**
 * Inserts a new discount voucher, i.e. a record into shopping_basket_voucher 
 * @param stdClass $voucher
 */
function add_voucher($voucher) {
    global $DB;
    
    $voucher->discountcode = ltrim(rtrim($voucher->discountcode));
    
    if (empty($voucher->discountcode)) {
        $voucher->discountcode = generate_discount_code();
    }
    
    $DB->insert_record('shopping_basket_voucher', $voucher);
}

/**
 * Updates an existing discount voucher record
 * @param stdclass $voucher
 */
function update_voucher($voucher) {
    global $DB;
    
    $DB->update_record('shopping_basket_voucher', $voucher);
}

/**
 * Returns a list of discount vouchers 
 * @return type
 */
function get_vouchers() {
    global $DB;
    
    return $DB->get_records('shopping_basket_voucher');
}

/**
 * Takes a given voucher code and validates it 
 * @param string $code Unique voucher code
 * @return string Validation error (if any)
 */
function validate_voucher($code) {
    global $DB;
    
    $voucher =  $DB->get_record('shopping_basket_voucher', array('discountcode' => $code));

    if (!$voucher) {
        return get_string('voucherinvalid', 'block_shopping_basket');
    }
    else {
        if (isset($voucher->expirydate)) {
            if ($voucher->expirydate < time()) {
                return get_string('voucherexpired', 'block_shopping_basket');
            }
        }
    }
    
    return '';
}

/**
 * Returns a specific voucher when passed the voucher code
 * @param string $code Voucher code
 * @return stdClass An shopping_basket_voucher record
 */
function get_voucher_by_code($code) {
    global $DB;
    
    $voucher =  $DB->get_record('shopping_basket_voucher', array('discountcode' => $code));
    
    return $voucher;
}

/**
 * Returns a specific voucher when given its ID
 * @param int $id Unique identifier for voucher
 * @return stdClass An shopping_basket_voucher record
 */
function get_voucher($id) {
    global $DB;
    
    $voucher =  $DB->get_record('shopping_basket_voucher', array('id' => $id));
    
    if (isset($voucher->expirydate)) {
        $voucher->enableexpiry = 1;
    }
    else {
        $voucher->enableexpiry = '';
    }
    
    return $voucher;
}

/**
 * Deletes a specified discount voucher 
 * @param int $id Unique identifier for voucher
 * @return bool
 */
function delete_voucher($id) {
    global $DB;
    
    return $DB->delete_records('shopping_basket_voucher', array('id' => $id));
}

/**
 * Generates a unique discount code (0-9, A-Z) of a required length.  Ensures 
 * the code is unique on the shopping_basket_voucher table
 * @param int $length
 * @return string Unique discount code
 */
function generate_discount_code($length = 8) {
    global $DB;
    $code = '';
    $count = 1;
    
    while ($count != 0) {
        $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        
        $count = $DB->count_records('shopping_basket_voucher', array('discountcode' => $code));
    }
    
    return $code;
}

/**
 * Updates a given product including any course
 * @global moodle_database $DB
 * @param stdClass $product
 */
function update_product($product) {
    global $DB, $USER;

    $courses = explode(',', $product->courseidlist);
    $categories = explode(',', $product->categoryidlist);
    $product = clean_product_data($product);
    $time = time();
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        $DB->update_record('shopping_basket_product', $product);
        
        if($product->hascategory == 0) {
            $previous_courses = $DB->get_records('shopping_basket_prod_course', array('product' => $product->id));

            $ids_to_delete = array();
            $courses_to_delete = array();
            $existing = array();

            // Work out which courses should be deleted from the product
            foreach ($previous_courses as $saved) {
                if (!in_array($saved->course, $courses)) {
                    $ids_to_delete[] = $saved->id;
                    $courses_to_delete[] = $saved->course;
                } 
                else {
                    $existing[] = $saved->course;
                }
            }

            if (count($ids_to_delete) > 0) {
                $DB->delete_records_list('shopping_basket_prod_course', 'id', $ids_to_delete);
            }

            // Work out the new courses
            $courses_to_add = array_diff($courses, $courses_to_delete);
            $courses_to_add = array_diff($courses_to_add, $existing);

            // Associate the courses
            foreach ($courses_to_add as $course) {
                $product_course = new stdClass();
                $product_course->product = $product->id;
                $product_course->course = $course;
                $product_course->timemodified = $time;
                $product_course->modifierid = $USER->id;

                $DB->insert_record('shopping_basket_prod_course', $product_course);
            }
        } else {
            $previous_categories = $DB->get_records('shopping_basket_prod_cat', array('product' => $product->id));

            $ids_to_delete = array();
            $categories_to_delete = array();
            $existing = array();

            // Work out which categories should be deleted from the product
            foreach ($previous_categories as $saved) {
                if (!in_array($saved->category, $categories)) {
                    $ids_to_delete[] = $saved->id;
                    $categories_to_delete[] = $saved->category;
                } 
                else {
                    $existing[] = $saved->category;
                }
            }

            if (count($ids_to_delete) > 0) {
                $DB->delete_records_list('shopping_basket_prod_cat', 'id', $ids_to_delete);
            }

            // Work out the new coursse
            $categories_to_add = array_diff($categories, $categories_to_delete);
            $categories_to_add = array_diff($categories_to_add, $existing);

            // Associate the courses
            foreach ($categories_to_add as $category) {
                $product_category = new stdClass();
                $product_category->product = $product->id;
                $product_category->category = $category;
                $product_category->timemodified = $time;
                $product_category->modifierid = $USER->id;

                $DB->insert_record('shopping_basket_prod_cat', $product_category);
            }
        }
        $DB->commit_delegated_transaction($transaction);
    }
    catch (Exception $e) {
        $transaction->rollback($e);
    }
}

/**
 * Takes a Moodle form submitted data and aligns it with the database representation
 * @global type $USER
 * @param stdClass $product
 * @return stdClass $product
 */
function clean_product_data($product) {
    global $USER;
    
    // Begin -- These properties are not stored in the database
    if (property_exists($product, 'submitbutton')) {
        unset($product->submitbutton);
    }
    
    if (property_exists($product, 'courseidlist')) {
        unset($product->courseidlist);
    }
    
    if (property_exists($product, 'categoryidlist')) {
        unset($product->categoryidlist);
    }
    // End
    
    if ($product->id == 0) {
        $product->timecreated = time();
    }
    
    if(isset($product->itemcode)) {
        $product->itemcode = ltrim(rtrim($product->itemcode));
    }
    
    $product->timemodified = time();
    $product->modifierid = $USER->id;
    
    return $product;
}

/**
 * Returns a list of courses
 * @global moodle_database $DB
 * @return course fields: id, fullname, visible
 */
function get_course_list() {
    global $DB;
    
    $courses = $DB->get_records_sql("SELECT id, fullname, visible
        FROM {course}
        ORDER BY fullname ASC");
    
    return $courses;
}

/**
 * Returns a list of products where which are associated with the specified course
 * @global moodle_database $DB
 * @param int $course Unique identifier of the course
 */
function get_products_for_course($course) {
    global $DB;
    $products = array();
    
    $products_by_course_sql = "SELECT p.id AS productid, p.*, c.course_count
            FROM {shopping_basket_prod_course} pc
            INNER JOIN {shopping_basket_product} p ON p.id = pc.product
            INNER JOIN (
                SELECT product, COUNT(course) AS course_count
                FROM {shopping_basket_prod_course}
                GROUP BY product
            ) c ON c.product = pc.product
            WHERE pc.course = :course 
                AND p.visible = 1
            ORDER BY course_count ASC";
    
    $by_course = $DB->get_records_sql($products_by_course_sql, array('course' => $course));
    
    if ($by_course) {
        foreach ($by_course as $course_product) {
            $products[$course_product->productid] = $course_product;
        }
    }
    
    $products_by_category_sql = "
        SELECT p.id AS productid, p.*
        FROM {shopping_basket_prod_cat} pc
        INNER JOIN {shopping_basket_product} p ON p.id = pc.product
        INNER JOIN {course_categories} cc ON cc.id = pc.category 
            OR cc.path LIKE CONCAT('%/', pc.category, '/%') 
            OR cc.path LIKE CONCAT('%/', pc.category)
        INNER JOIN {course} c ON c.category = cc.id
        WHERE
            c.id = :course
        AND
            p.visible = 1
        ";
    
    $by_category = $DB->get_records_sql($products_by_category_sql, array('course' => $course));
    
    if ($by_category) {
        foreach ($by_category as $category_product) {
            $products[$category_product->productid] = $category_product;
        }
    }
    
    return $products;
}

/**
 * Generates the HTML form markup for a specified product
 * @param stdClass $product
 * @return string HTML <form> markup
 */
function generate_product_html($product) {
    $currency = get_config('block_shopping_basket', 'currency');
    
    $html = '';
    $html .= html_writer::start_tag('form', array('class' => 'for_sale', 'action' => '', 'method' => 'post'));
    $html .= "\n";
    $html .= html_writer::start_tag('fieldset');
    $html .= "\n";

    $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'my-item-id', 'value' => $product->id));
    $html .= "\n";
    $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'my-item-name', 'value' => $product->fullname));
    $html .= "\n";
    $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'my-item-price', 'value' => $product->cost));
    $html .= "\n";
    $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'my-item-url', 'value' => ''));
    $html .= "\n";
    
    $html .= html_writer::start_tag('ul');
    $html .= "\n";
    
    $html .= html_writer::tag('li',  html_writer::tag('span', get_string('itemcode', 'block_shopping_basket'), array('class' => 'product_label')) . html_writer::tag('span', $product->itemcode));
    $html .= "\n";
    $html .= html_writer::tag('li', html_writer::tag('span', get_string('productname', 'block_shopping_basket'), array('class' => 'product_label')) . html_writer::tag('span', $product->fullname));
    $html .= "\n";
    $html .= html_writer::tag('li',  html_writer::tag('span', get_string('price', 'block_shopping_basket'), array('class' => 'product_label'))  .  html_writer::tag('span', $currency . '&nbsp;' . number_format($product->cost, 2)));
    $html .= "\n";
    $html .= html_writer::tag('li', html_writer::tag('span', get_string('quantity', 'block_shopping_basket'), array('class' => 'product_label')) 
            . html_writer::tag('span', html_writer::empty_tag('input', array('type' => 'text', 'autocomplete' => 'off', 'name' => 'my-item-qty', 'maxlength' => 3, 'size' => 3, 'value' => 1))));
    $html .= "\n";
    $html .= html_writer::end_tag('ul');
    $html .= "\n";
    
    $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'my-add-button', 'class' => 'button', 'value' => get_string('addtobasket', 'block_shopping_basket')));
    $html .= "\n";
    $html .= html_writer::end_tag('fieldset');
    $html .= "\n";
    $html .= html_writer::end_tag('form');
    
    return $html;
}

/**
 * Returns the HTML which will be rendered on the premium enrol.html page
 * @param int $course Unique identifier of the course
 * @return string HTML string corresponding to the 
 */
function get_enrolment_html($course) {
    $html = '';
    $products = get_products_for_course($course);
    
    if ($products) {
        $count = 0;
        foreach ($products as $product) {
            $html .= generate_product_html($product);
            
            $count++;
        }
//        if (count($products) == 1) {
//            $html = generate_product_html($products[0]);
//        }
    }
    else {
        // No products exist for this course
        $html = html_writer::tag('div', get_string('notconfigureforpurchase', 'block_shopping_basket'), array('id' => 'course_not_for_sale'));
    }
    
    return $html;
}

/**
 * Iterate over each and confirm the prices
 * @param array stdClass $basket
 */
function validate_basket_items($items) {
    $valid = false;
    
    foreach ($items as $item) {
        $product = get_product($item->id);
        
        if ($product->cost == $item->price) {
            $valid = true;
        }
        else {
            $valid = false;
            break;
        }
    }
    
    return $valid;
}

/**
 * Return a list of orders pending approval
 * @global moodle_database $DB
 * @return type
 */
function get_pending_purchase_orders() {
    global $DB;
    
    return $DB->get_records_sql("SELECT o.id, o.po_number, o.total, o.currency, u.id AS userid, u.firstname, u.lastname, o.timecreated
        FROM {shopping_basket_order} o
        INNER JOIN {user} u ON u.id = o.userid
        WHERE o.po_number IS NOT NULL AND o.payment_status IS NULL
        ORDER BY o.timecreated DESC");
}

function get_completed_purchase_orders() {
    global $DB;
    
    return $DB->get_records_sql("SELECT o.id, o.po_number, o.total, o.currency, u.id AS userid, u.firstname, u.lastname, o.timecreated
        FROM {shopping_basket_order} o
        INNER JOIN {user} u ON u.id = o.userid
        WHERE o.po_number IS NOT NULL AND o.payment_status = ?
        ORDER BY o.timecreated DESC", array(PAYMENT_STATUS_COMPLETED));
}

/**
 * Inserts details of a new order into the database
 * @global moodle_database $DB
 * @param stdClass $basket
 */
function add_order($basket, $ponumber = '') {
    global $USER, $DB;
    
    $order_id = '';
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        // First insert the order header
        $new_order = new stdClass();
        $new_order->userid = $USER->id;
        $new_order->business = get_config('block_shopping_basket', 'vendoridentifier');
        $new_order->currency = get_config('block_shopping_basket', 'currency');
        $new_order->payment_provider = get_config('block_shopping_basket', 'paymentprovider');
        $new_order->tax = $basket->totaltax;
        $new_order->total = $basket->total;
        $new_order->discount = $basket->discountamount;
        $new_order->discountcode = isset($basket->vouchercode) && !empty($basket->vouchercode) ? $basket->vouchercode : null;
        
        if ($ponumber !== '') {
            $new_order->po_number = $ponumber;
            $new_order->payment_status = PAYMENT_STATUS_COMPLETED;
            $new_order->fulfilled = 0;
        }
        
        $new_order->timecreated = time();
        $new_order->timemodified = time();
        
        $order = $DB->insert_record('shopping_basket_order', $new_order, true);
        
        // Add the order details
        foreach ($basket->items as $item) {
            $detail = new stdClass();
            
            $detail->orderid = $order;
            $detail->productid = $item->id;
            $detail->quantity = $item->quantity;
            $detail->cost = $item->price;
            $detail->linetotal = $item->linetotal;
            $detail->linetax = $item->linetax;
            
            $DB->insert_record('shopping_basket_order_detail', $detail);
        }
        
        $transaction->allow_commit();
        
        // Only stamp the order Id when everything has succeeded
        $order_id = $order;
    }
    catch (Exception $ex) {
        $transaction->rollback($ex);
    }
    
    return $order_id;
}

/**
 * Returns a shopping_basket_order record for a given id
 * @param int $id Unique identifier for the order
 * @return type
 */
function get_order($id) {
    global $DB;
    
    return $DB->get_record('shopping_basket_order', array('id' => $id), '*', MUST_EXIST);
}

/**
 * Gets details for a specified order
 * @param int $orderid Unique identifier of order
 * @return type
 */
function get_order_details($orderid) {
    global $DB;
    
    $sql = "SELECT d.productid, d.quantity, d.cost, d.linetotal, d.linetax, p.itemcode, p.fullname, p.description 
            FROM {shopping_basket_order_detail} d
            INNER JOIN {shopping_basket_product} p ON p.id = d.productid
            WHERE d.orderid = :orderid";
    
    return $DB->get_records_sql($sql, array('orderid' => $orderid));
}

/**
 * Update an Order
 * @global moodle_database $DB
 * @param stdClass $order
 */
function update_order( $order )
{
    global $DB;
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        $DB->update_record('shopping_basket_order', $order);
        $transaction->allow_commit();
    }
    catch (Exception $ex) {
        $transaction->rollback($ex);
    }
}

/**
 * Process any voucher associated with the order
 * @param int $orderid Unique identifier of order
 */
function process_voucher($orderid) {
    global $DB;
    
    $order = get_order($orderid);
    
    if (isset($order->discountcode)) {
        $voucher = get_voucher_by_code($order->discountcode);

        if ($voucher->singleuse) {
            // This was a one time voucher which has been used
            // so expire the voucher
            $voucher->expirydate = time();
            $voucher->expired = 1;
            
            $DB->update_record('shopping_basket_voucher', $voucher);
        } 
    }
}

/**
 * Process the orders that a user has taken
 * @global moodle_database $DB
 * @param type $order_id
 */
function process_purchase($orderid) {
    global $DB;

    $license_purchase = false;
    
    // Get the student role
    $student = $DB->get_record('role', array('shortname' => 'student'));
    
    $order = $DB->get_record('shopping_basket_order', array('id' => $orderid, 'fulfilled' => 0, 'payment_status' => PAYMENT_STATUS_COMPLETED));
    
    if ($order) {
        $purchases = $DB->get_records('shopping_basket_order_detail', array('orderid' => $orderid));
        
        try {
            foreach ($purchases as $purchase) {
                if ($purchase->quantity == 1) {
                    // When only one item is bought, treat this as an enrolment
                    // for the user who bought it - unless autoenrol == 0
                    $product = get_product($purchase->productid);

                    // Insert 1 license - not used by the user currently, though
                    // used to check for post-purchase enrolments etc;
                    $license = new stdClass();
                    $license->licensekey = generate_license_key();
                    $license->orderid = $order->id;
                    $license->productid = $purchase->productid;
                    $license->userid = $order->userid;
                    
                    if (get_config('block_shopping_basket', 'enrolonpurchasedate')) {
                        $license->startdate = time();
                        $license->enddate = time() + $product->enrolperiod;
                    }
                    else {
                        $license->startdate = null;
                        $license->enddate = null;
                    }
                    
                    $lid = $DB->insert_record('shopping_basket_license', $license);
                    
                    // Only enrol if auto-enrolment is enabled
                    if(get_config('enrol_premium','autoenrol')==1) {
                        foreach ($product->courses as $course) {
                            $timestart = time();
                            $timeend = time() + $product->enrolperiod;

                            // Enrol the user who placed the order
                            enrol_premium_user($course, $order->userid, $timestart, $timeend);
                        }
                        // Update our license for consistency
                        $updated_license = new stdClass();
                        $updated_license->id = $lid;
                        $updated_license->startdate = $timestart;
                        $updated_license->enddate = $timeend;
                        $DB->update_record('shopping_basket_license', $updated_license);
                    }
                }
                else {
                    $license_purchase = true;
                    
                    $product = get_product($purchase->productid);
                    
                    // This was a license purchase
                    for ($i = 1; $i <= intval($purchase->quantity); $i++) {
                        // Insert the specified quantity of licenses
                        $license = new stdClass();
                        $license->licensekey = generate_license_key();
                        $license->orderid = $order->id;
                        $license->productid = $purchase->productid;
                        
                        if (get_config('block_shopping_basket', 'enrolonpurchasedate')) {
                            $license->startdate = time();
                            $license->enddate = time() + $product->enrolperiod;
                        }
                        else {
                            $license->startdate = null;
                            $license->enddate = null;
                        }
                        
                        $DB->insert_record('shopping_basket_license', $license);
                    }
                }       
            }

            // Confirm that the order has been fulfilled
            $check = check_order_fulfilled($order);
            if( $check->fulfilled ) {
                $order->fulfilled = 1;
                $order->timemodified = time();
                $DB->update_record('shopping_basket_order', $order);
            }
            
            // Send email?
            if ($license_purchase) {
                send_license_email_to_user($orderid);
            }
        }
        catch (Exception $e) {
            add_to_payment_error_log($order->id, '', $e->getMessage());
        }        
    }
}

/**
 * Enrol the specified user on the specified course using the 'premium' 
 * enrolment method
 * @param stdClass $course
 * @param int $userid Unique identifier for the user
 * @param int $timestart The date/time to start the enrolment
 * @param int $timeend The date/time the enrolment should end
 */
function enrol_premium_user($course, $userid, $timestart = 0, $timeend = 0) {
    global $DB;
    
    $enrol = true;
    
     // Get the student role
    $student = $DB->get_record('role', array('shortname' => 'student'));
    
    // Enrol the specified user
    $plugin = enrol_get_plugin('premium');

    // Check if the enrolment plugin is enabled on the specified course
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'premium'));

    if (!$instance) {
        $sortorder = $DB->count_records('enrol', array('courseid' => $course->id));

        $sortorder = ($sortorder > 0 )? $sortorder + 1 : 0;
        $enrol_record = new stdClass();
        $enrol_record->enrol = 'premium';
        $enrol_record->status = 0;
        $enrol_record->courseid = $course->id;
        $enrol_record->sortorder = $sortorder;
        $enrol_record->enrolperiod = 0;
        $enrol_record->cost = 0.01;
        $enrol_record->currency = get_config('block_shopping_basket', 'currency');
        $enrol_record->roleid = $student->id;
        $enrol_record->timecreated = time();
        $enrol_record->timemodified = time();

        $DB->insert_record('enrol', $enrol_record);

        $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'premium'));
    }

    if ($ue = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid))) {
        if ($ue->timeend > $timeend) {
            // User is already enrolled and for a longer period
            $enrol = false;
        }
        else {
            $timestart = $ue->timestart;
        }
    }
    else {
        $timestart = time();
    }

    if ($enrol) {
        // OK to proceed with enrolment
        $plugin->enrol_user($instance, $userid, $student->id, $timestart, $timeend);
        
        // send welcome
        if ($instance->customint4) {
            $user = $DB->get_record('user', array('id' => $userid));
            $plugin->email_welcome_message($instance, $user);
        }
    }
}

function send_individual_license($email, $licensecode) {
    global $CFG, $USER;
    
    $returnvalue = false;
    
    $data = new stdClass();
    $data->firstname = $USER->firstname;
    $data->lastname = $USER->lastname;
    $data->link = $CFG->wwwroot;
    $data->licensecode = $licensecode;
    
    $subject = get_string('assignlicensesubject', 'block_shopping_basket');
    $message = get_string('assignlicensebody', 'block_shopping_basket', $data);
    
    $userto = new Object();
    $userto->email = $email;
    $userto->mailformat = 0; // 0 - Plain text
    $userfrom = $CFG->noreplyaddress;

    try {
        email_to_user($userto, $userfrom, $subject, $message);
        $returnvalue = true;
    }
    catch(Exception $ex) {
        $returnvalue = false;
    }
    
    return $returnvalue;
}

/**
 * Sends an email to the user who placed the order including license keys
 * @param int $orderid Unique identifier of order
 */
function send_license_email_to_user($orderid) {
    global $CFG, $DB;
    
    // Get the order
    $order = $DB->get_record('shopping_basket_order', array('id' => $orderid));
    
    // Get the user who placed the order
    $user = $DB->get_record('user', array('id' => $order->userid));
    
    $subject = get_string('licenseordersubject', 'block_shopping_basket');
    $messagetext = get_string('licenseordermessagebody', 'block_shopping_basket');
    
    $messagetext .= "\n\n";
    
    // Get the license keys
    $licenses = $DB->get_records('shopping_basket_license', array('orderid' => $orderid, 'userid' => NULL));
    
    $productid = 0;
    
    foreach ($licenses as $license) {
        if ($productid != $license->productid) {
            // Output details of the purchase, i.e. the product name and duration
            $product = get_product($license->productid);
        
            $messagetext .= $product->fullname . ' (' . seconds_to_text($product->enrolperiod) . '):' ."\n";
            
            $productid = $license->productid;
        }
        
        $messagetext .= $license->licensekey . "\n";
    }
    
    $messagetext .= "\n";
    $messagetext .= get_string('licenseordermessagefooter', 'block_shopping_basket', $CFG->wwwroot . '/blocks/shopping_basket/license_manager.php');
    
    $from_address = $CFG->noreplyaddress;
    
    email_to_user($user, $from_address, $subject, $messagetext);
}

/**
 * Generates a unique license key with number 0-9 and letters A-Z
 * @global moodle_database $DB
 * @param int $length Must be a multiple of 4
 * @return string Unique license key in the form XXXX-XXXX-XXXX-XXXX
 */
function generate_license_key($length = 16) {
    global $DB;
    $key = '';
    $count = 1;
    
    while ($count != 0) {
        $key = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    
        $pieces = str_split($key, $length / 4);
        $key = implode('-', $pieces);
    
        $count = $DB->count_records('shopping_basket_license', array('licensekey' => $key));
    }
    
    return $key;
}

/**
 * Inserts a record of the payment error
 * @global moodle_database $DB
 * @param int $orderid Unique identifier of the order
 * @param string $response The raw response received from the payment provide
 * @param string $error Description of the error which has occured
 */
function add_to_payment_error_log($orderid = 0, $response = '', $error = '') {
    global $DB;
    
    $log_entry = new stdClass();
    $log_entry->orderid = $orderid;
    $log_entry->response = $response;
    $log_entry->message = $error;
    $log_entry->time = time();
    
    $DB->insert_record('shopping_basket_log', $log_entry);
}

/**
 * Generate HTML for the License Manager link
 * @global type $USER
 * @return html
 */
function display_license_manager_link() {
    global $USER;
    $link = '';

    if(get_user_license_overview($USER->id)) {
        $link = html_writer::link(new moodle_url('/blocks/shopping_basket/license_manager.php'), get_string('licensemanagerlink', 'block_shopping_basket'));
    }
    return $link;
}

/**
 * Fetch User Licenses
 * @global moodle_database $DB
 * @return array
 */
function get_user_license_overview($userid) {
    global $DB;
    
    $overview = $DB->get_records_sql("
        SELECT p.id AS productid,
            p.itemcode,
            p.fullname AS productname,
            COUNT(l.id) AS total,
            COUNT(l2.id) AS used
        FROM {shopping_basket_license} l
        INNER JOIN {shopping_basket_product} p ON p.id = l.productid
        LEFT JOIN {shopping_basket_license} l2 ON l2.id = l.id AND l2.userid IS NOT NULL
        WHERE l.orderid IN (
            SELECT o.id 
            FROM {shopping_basket_order} o
            INNER JOIN {shopping_basket_order_detail} od ON od.orderid = o.id
            WHERE o.userid = :userid 
            AND o.payment_status = '" . PAYMENT_STATUS_COMPLETED . "' 
            AND od.productid = l.productid
            AND od.quantity > 1
        )
        GROUP by p.id
        ", array('userid' => $userid));
    
    return $overview;
}

/**
 * Fetch User Product License records
 * @global moodle_database $DB
 * @param int $userid
 * @param int $productid
 * @param string $filter (optional) pass 'used' or 'free' to filter licenses
 * @return array
 */
function get_user_product_licenses($userid, $productid, $filter = '') {
    global $DB;
    
    $used_or_free = '';
    if($filter == 'used') {
        $used_or_free = " AND l.userid IS NOT NULL ";
    } else if($filter == 'free') {
        $used_or_free = " AND l.userid IS NULL ";
    }
    
    $licenses = $DB->get_records_sql("
        SELECT
            l.id,
            l.startdate,
            l.enddate,
            l.licensekey AS code,
            l.id AS orderid,
            p.id AS productid,
            p.itemcode,
            p.fullname AS productname,
            CONCAT(lu.firstname,' ', lu.lastname) AS username,
            lu.email AS useremail
        FROM {shopping_basket_license} l
        INNER JOIN {shopping_basket_product} p ON p.id = l.productid
        LEFT  JOIN {user} lu ON lu.id = l.userid
        WHERE p.id = :productid
        AND l.orderid IN (
            SELECT o.id 
            FROM {shopping_basket_order} o
            INNER JOIN {shopping_basket_order_detail} od ON od.orderid = o.id
            WHERE o.userid = :userid 
            AND o.payment_status = '" . PAYMENT_STATUS_COMPLETED . "'
            AND od.productid = l.productid
            AND od.quantity > 1
        ) ".$used_or_free,
        array('userid' => $userid, 'productid' => $productid));
    return $licenses;
}

/**
 * Convert seconds x days, x hours, x minutes, x seconds
 * @param type $s
 * @return string
 */
function seconds_to_text($s) {
    $duration = array();
    
    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;

    // Extract days
    $days = floor($s / $secondsInADay);

    // Extract hours
    $hourSeconds = $s % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    if ($days > 0) $duration[] = "$days days";
    if ($hours > 0 ) $duration[] = "$hours hours";
    if ($minutes > 0 ) $duration[] = "$minutes minutes";
    if ($seconds > 0) $duration[] = "$seconds seconds";
    
    return join(', ', $duration);
}

/**
 * Check order user details
 * Automatically registers a new user if required
 * @global moodle_database $DB
 * @param type $user
 * @return type
 */
function check_order_user( $ipn_data ) {
    global $DB;
    $user = false;
    
    // Check if email address belongs to a user
    if ( $existing_user = $DB->get_record("user", array("email" => $ipn_data->payer_email)) ) {
        // User exists, so no need to register
        $user = $existing_user;
    } else {
        // User doesn't exist, register them
        $user = register_user_from_ipn($ipn_data);
    }
    
    return $user;
}

/**
 * Register a user if they do not exist,
 * using details from PayPal IPN
 * @global type $CFG
 * @global moodle_database $DB
 */
function register_user_from_ipn( $ipn_data ) {
    global $CFG;
    
    $user = new stdClass();
    $user->username    = strtolower($ipn_data->payer_email);
    $user->firstname   = $ipn_data->first_name;
    $user->lastname    = $ipn_data->last_name;
    $user->email       = $ipn_data->payer_email;
    $user->city        = $ipn_data->address_city;
    $user->country     = $ipn_data->address_country_code;
    $user->confirmed   = 0;
    $user->lang        = current_language();
    $user->firstaccess = time();
    $user->timecreated = time();
    $user->mnethostid  = $CFG->mnet_localhost_id;
    $user->secret      = random_string(15);
    $user->auth        = 'email';
    $user->password    = 'welcome';
    
    // Register the user
    $registered = ipn_signup_user($user, $ipn_data->orderid);
    
    if (!$registered) {
        return false;
    }
    
    // Force password change
    set_user_preference('auth_forcepasswordchange', 1, $user->id);
    
    return $user;
}

/**
 * Register a new user via ipn data
 * @global type $CFG
 * @global moodle_database $DB
 * @param type $user
 * @return type
 */
function ipn_signup_user($user, $orderid) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $passwordplain = $user->password;
    $user->password = hash_internal_user_password($user->password);

    $user->id = $DB->insert_record('user', $user);

    /// Save any custom profile field information
    profile_save_data($user);

    $user = $DB->get_record('user', array('id'=>$user->id));
    events_trigger('user_created', $user);
    
    $user->passwordplain = $passwordplain;
    if (! send_confirmation_email_to_user($user) ) {
        add_to_payment_error_log($orderid, '', 'Confirmation email to ' . $user->email . ' failed');
    }
    
    return $user;
}

/**
 * Check purchase was processed successfully
 * @param stdClass $order
 * @return boolean
 */
function check_order_fulfilled( $order ) {
    global $DB;
    $retval = new stdClass();
    $fulfilled = false;
    $errors = array();
    
    $order_details = get_order_details($order->id);
    
    foreach ( $order_details as $order_detail ) {
    
        if( $order_detail->quantity > 1 || get_config('enrol_premium','autoenrol')==0 ) {
            // Ensure all purchased licenses have been generated
            $params = array('orderid' => $order->id, 'productid' => $order_detail->productid);
            $license_count = $DB->count_records('shopping_basket_license', $params);
            
            if ( $order_detail->quantity == $license_count ) {
                $fulfilled = true;
            } else {
                $msg = 'Product ID:'.$order_detail->productid.' Item Code: '.$order_detail->itemcode.' Name: ' .$order_detail->fullname;
                $msg.= ' - '.$license_count.' licenses generated, '.$order_detail->quantity.' ordered';
                $errors[] = $msg;
                add_to_payment_error_log($order->id,'', $msg);
                $fulfilled = false;
            }
        } else {
            // Ensure user is enrolled on all purchased courses
            $product_course_ids = $enrolled_course_ids = array();
            $product = get_product($order_detail->productid);

            foreach( $product->courses as $course ) {
                $product_course_ids[$course->id] = $course->id;
            }
            
            $user_enrolments = $DB->get_records_sql("
                SELECT e.courseid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                WHERE ue.userid = :userid
                AND e.enrol = :enrol",
                array('userid' => $order->userid, 'enrol' => 'premium')
            );

            foreach ($user_enrolments as $enrolment) {
                $enrolled_course_ids[$enrolment->courseid] = $enrolment->courseid;
            }

            // Compare purchased courses with enrolled courses
            $diff = array_diff_key($product_course_ids, $enrolled_course_ids);
            
            if( empty($diff) ) {
                $fulfilled = true;
            } else {
                // User is not enrolled on all purchased courses, don't fulfill the order
                $msg = 'Product ID:'.$order_detail->productid.' Item Code: '.$order_detail->itemcode.' Name: ' .$order_detail->fullname;
                $msg.= ' - User not enrolled on courses: '.join(',',$diff);
                $errors[] = $msg;
                add_to_payment_error_log($order->id, '', $msg);
                $fulfilled = false;
            }
        }
    }
    $retval->fulfilled = $fulfilled;
    $retval->errors = $errors;
    return $retval;
}

/**
 * Replicating core moodle email_to_user function to allow adding of CC. We want the email behaviour to be 
 * identical otherwise, so this seems the most straightfoward, if wasteful, method :-\  
 * 
 * @global class $CFG
 * @global type $FULLME
 * @param type $user
 * @param type $from
 * @param type $subject
 * @param type $messagetext
 * @param type $usecc
 * @param type $messagehtml
 * @param type $attachment
 * @param type $attachname
 * @param type $usetrueaddress
 * @param type $replyto
 * @param type $replytoname
 * @param type $wordwrapwidth
 * @return boolean 
 */
function shopping_basket_email_to_user($user, $from, $subject, $messagetext, $usecc=false, $messagehtml='', $attachment='', $attachname='', $usetrueaddress=true, $replyto='', $replytoname='', $wordwrapwidth=79) {
    global $CFG, $FULLME;

    if (is_array($user)) {
        $user = current($user);
    }
    
    if (empty($user) || empty($user->email)) {
        $nulluser = 'User is null or has no email';
        error_log($nulluser);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$nulluser);
        }
        return false;
    }

    if (!empty($user->deleted)) {
        // do not mail deleted users
        $userdeleted = 'User is deleted';
        error_log($userdeleted);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$userdeleted);
        }
        return false;
    }

    if (!empty($CFG->noemailever)) {
        // hidden setting for development sites, set in config.php if needed
        $noemail = 'Not sending email due to noemailever config setting';
        error_log($noemail);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$noemail);
        }
        return true;
    }

    if (!empty($CFG->divertallemailsto)) {
        $subject = "[DIVERTED {$user->email}] $subject";
        $user = clone($user);
        $user->email = $CFG->divertallemailsto;
    }

    // skip mail to suspended users
    if ((isset($user->auth) && $user->auth=='nologin') or (isset($user->suspended) && $user->suspended)) {
        return true;
    }

    if (!validate_email($user->email)) {
        // we can not send emails to invalid addresses - it might create security issue or confuse the mailer
        $invalidemail = "User $user->id (".fullname($user).") email ($user->email) is invalid! Not sending.";
        error_log($invalidemail);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$invalidemail);
        }
        return false;
    }

    if (over_bounce_threshold($user)) {
        $bouncemsg = "User $user->id (".fullname($user).") is over bounce threshold! Not sending.";
        error_log($bouncemsg);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$bouncemsg);
        }
        return false;
    }

    // If the user is a remote mnet user, parse the email text for URL to the
    // wwwroot and modify the url to direct the user's browser to login at their
    // home site (identity provider - idp) before hitting the link itself
    if (is_mnet_remote_user($user)) {
        require_once($CFG->dirroot.'/mnet/lib.php');

        $jumpurl = mnet_get_idp_jump_url($user);
        $callback = partial('mnet_sso_apply_indirection', $jumpurl);

        $messagetext = preg_replace_callback("%($CFG->wwwroot[^[:space:]]*)%",
                $callback,
                $messagetext);
        $messagehtml = preg_replace_callback("%href=[\"'`]($CFG->wwwroot[\w_:\?=#&@/;.~-]*)[\"'`]%",
                $callback,
                $messagehtml);
    }
    $mail = get_mailer();

    if (!empty($mail->SMTPDebug)) {
        echo '<pre>' . "\n";
    }

    $temprecipients = array();
    $tempreplyto = array();

    $supportuser = generate_email_supportuser();

    // make up an email address for handling bounces
    if (!empty($CFG->handlebounces)) {
        $modargs = 'B'.base64_encode(pack('V',$user->id)).substr(md5($user->email),0,16);
        $mail->Sender = generate_email_processing_address(0,$modargs);
    } else {
        $mail->Sender = $supportuser->email;
    }

    if (is_string($from)) { // So we can pass whatever we want if there is need
        $mail->From     = $CFG->noreplyaddress;
        $mail->FromName = $from;
    } else if ($usetrueaddress and $from->maildisplay) {
        $mail->From     = $from->email;
        $mail->FromName = fullname($from);
    } else {
        $mail->From     = $CFG->noreplyaddress;
        $mail->FromName = fullname($from);
        if (empty($replyto)) {
            $tempreplyto[] = array($CFG->noreplyaddress, get_string('noreplyname'));
        }
    }

    if (!empty($replyto)) {
        $tempreplyto[] = array($replyto, $replytoname);
    }

    $mail->Subject = substr($subject, 0, 900);

    $temprecipients[] = array($user->email, fullname($user));

    $mail->WordWrap = $wordwrapwidth;                   // set word wrap

    if (!empty($from->customheaders)) {                 // Add custom headers
        if (is_array($from->customheaders)) {
            foreach ($from->customheaders as $customheader) {
                $mail->AddCustomHeader($customheader);
            }
        } else {
            $mail->AddCustomHeader($from->customheaders);
        }
    }

    if (!empty($from->priority)) {
        $mail->Priority = $from->priority;
    }

    if ($messagehtml && !empty($user->mailformat) && $user->mailformat == 1) { // Don't ever send HTML to users who don't want it
        $mail->IsHTML(true);
        $mail->Encoding = 'quoted-printable';           // Encoding to use
        $mail->Body    =  $messagehtml;
        $mail->AltBody =  "\n$messagetext\n";
    } else {
        $mail->IsHTML(false);
        $mail->Body =  "\n$messagetext\n";
    }

    if ($attachment && $attachname) {
        if (preg_match( "~\\.\\.~" ,$attachment )) {    // Security check for ".." in dir path
            $temprecipients[] = array($supportuser->email, fullname($supportuser, true));
            $mail->AddStringAttachment('Error in attachment.  User attempted to attach a filename with a unsafe name.', 'error.txt', '8bit', 'text/plain');
        } else {
            require_once($CFG->libdir.'/filelib.php');
            $mimetype = mimeinfo('type', $attachname);
            $mail->AddAttachment($CFG->dataroot .'/'. $attachment, $attachname, 'base64', $mimetype);
        }
    }

    // Check if the email should be sent in an other charset then the default UTF-8
    if ((!empty($CFG->sitemailcharset) || !empty($CFG->allowusermailcharset))) {

        // use the defined site mail charset or eventually the one preferred by the recipient
        $charset = $CFG->sitemailcharset;
        if (!empty($CFG->allowusermailcharset)) {
            if ($useremailcharset = get_user_preferences('mailcharset', '0', $user->id)) {
                $charset = $useremailcharset;
            }
        }

        // convert all the necessary strings if the charset is supported
        $charsets = get_list_of_charsets();
        unset($charsets['UTF-8']);
        if (in_array($charset, $charsets)) {
            $textlib = textlib_get_instance();
            $mail->CharSet  = $charset;
            $mail->FromName = $textlib->convert($mail->FromName, 'utf-8', strtolower($charset));
            $mail->Subject  = $textlib->convert($mail->Subject, 'utf-8', strtolower($charset));
            $mail->Body     = $textlib->convert($mail->Body, 'utf-8', strtolower($charset));
            $mail->AltBody  = $textlib->convert($mail->AltBody, 'utf-8', strtolower($charset));

            foreach ($temprecipients as $key => $values) {
                $temprecipients[$key][1] = $textlib->convert($values[1], 'utf-8', strtolower($charset));
            }
            foreach ($tempreplyto as $key => $values) {
                $tempreplyto[$key][1] = $textlib->convert($values[1], 'utf-8', strtolower($charset));
            }
        }
    }

    foreach ($temprecipients as $values) {
        $mail->AddAddress($values[0], $values[1]);
    }
    foreach ($tempreplyto as $values) {
        $mail->AddReplyTo($values[0], $values[1]);
    }
    
    if ($usecc) {
        // send notification to cc email if exists
        $cclist = explode(',', get_config('block_shopping_basket','alertemail'));
        if (!empty($cclist)) {            
            foreach ($cclist as $cc) {
                $mail->AddCC($cc);
            }
        } 
    }

    if ($mail->Send()) {
        set_send_count($user);
        $mail->IsSMTP();                               // use SMTP directly
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return true;
    } else {
        add_to_log(SITEID, 'library', 'mailer', $FULLME, 'ERROR: '. $mail->ErrorInfo);
        if (CLI_SCRIPT) {
            mtrace('Error: blocks/shopping_basket/lib.php shopping_basket_email_to_user(): '.$mail->ErrorInfo);
        }
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return false;
    }           
}

/**
 * Inserts product discounts
 * @global moodle_database $DB
 * @param stdClass $product
 */
function update_product_discounts($discounts) {
    global $DB;

    $transaction = $DB->start_delegated_transaction();
    
    try {
        
        // Add discounts
        foreach ($discounts as $discount) {
            if ( $discount->id == 0 ) {
                $DB->insert_record('shopping_basket_prod_disc', $discount);
            } else {
                if( $discount->delete != 1 ) {
                    $DB->update_record('shopping_basket_prod_disc', $discount);
                } else {
                    $DB->delete_records('shopping_basket_prod_disc', array('id' => $discount->id));
                }
            }
        }
        
        $transaction->allow_commit();
    }
    catch (Exception $e) {
        $transaction->rollback($e);
    }
}

/**
 * Returns a list of product discounts
 * @global moodle_database $DB
 * @return discount fields
 */
function get_product_discounts($productid) {
    global $DB;
    
    $discounts = $DB->get_records_sql("SELECT id, min, max, rate, criteria, discounttype
        FROM {shopping_basket_prod_disc}
        WHERE productid = :productid
        ", array('productid' => $productid));
    
    return $discounts;
}

/**
 * Return product discount amount - returns the amount, not a percentage
 * @global moodle_database $DB
 * @param type $productid
 * @param type $quantity
 * @return type
 */
function get_discount( $productid, $quantity ) {
    global $DB;
    $retval = new stdClass();
    $retval->amount = 0;
    $retval->rate = 0;
    $retval->type = false;
    $discount = $DB->get_record_sql("SELECT d.id, d.rate, d.criteria, p.cost
        FROM {shopping_basket_prod_disc} d
        INNER JOIN {shopping_basket_product} p ON p.id = d.productid
        WHERE productid = :productid
        AND   d.min <= :minquantity
        AND   d.max >= :maxquantity
        ", array('productid' => $productid, 'minquantity' => $quantity, 'maxquantity' => $quantity));
    if( $discount ) {
        switch ($discount->criteria) {
            case TIER_CRITERIA_PERCENTAGE:
                $percentage = (1 - floatval($discount->rate / 100));
                $retval->amount = round(($discount->cost - ($discount->cost * $percentage)),2);
                $retval->rate = number_format($discount->rate, 2);
                $retval->type = TIER_CRITERIA_PERCENTAGE;
                break;

            case TIER_CRITERIA_FIXED_AMOUNT:
                // Added security against price changes, without 
                // corresponding product discount updates
                if($discount->rate <= $discount->cost) {
                    $retval->amount = round($discount->rate,2);
                    $retval->rate = number_format($discount->rate,2);
                    $retval->type = TIER_CRITERIA_FIXED_AMOUNT;
                }
                break;
        }
    }
    return $retval;
}

/**
 * Regenerate a License Key
 * @param type $licenseid
 */
function regenerate_license($licenseid) {
    global $DB, $USER;
    
    // Ensure the user performing the action 
    // is the user who purchased the license,
    // and the license isn't assigned (userid is null)
    $license = $DB->get_record_sql("SELECT l.id
        FROM {shopping_basket_license} l
        INNER JOIN {shopping_basket_order} o ON o.id = l.orderid
        WHERE l.id = :licenseid
        AND   o.userid = :userid
        AND   l.userid IS NULL
        ", array('licenseid' => $licenseid, 'userid' => $USER->id));
    
    if ($license) {
        $license->licensekey = generate_license_key();
        return $DB->update_record('shopping_basket_license', $license);
    } else {
        return false;
    }
}

/**
 * Send an email to a newly created user, instructing them
 * to confirm their account.
 * @global type $CFG
 * @param type $user
 * @return bool true if mail is sent
 */
function send_confirmation_email_to_user($user) {
    global $CFG;
    
    $site = get_site();
    $supportuser = generate_email_supportuser();

    $data = new stdClass();
    $data->firstname = fullname($user);
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();
    $data->username = $user->username;
    $data->password = $user->passwordplain;
    
    $subject = get_string('emailconfirmationsubject', '', format_string($site->fullname));

    $username = urlencode($user->username);
    $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
    
    $data->link  = $CFG->wwwroot .'/login/confirm.php?data='. $user->secret .'/'. $username;
    
    $message     = get_string('shoppingbasketuserconfirm', 'block_shopping_basket', $data);
    $messagehtml = text_to_html(get_string('shoppingbasketuserconfirm', 'block_shopping_basket', $data), false, false, true);
    
    $user->mailformat = 1;  // Always send HTML version
    return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
}

/**
 * Search for a user license that includes a course.
 * Returns the license with the latest enddate.
 * If no enddate is assigned, the maximum enrolperiod found across
 * the users purchased products (containing the course) is returned also.
 * @global moodle_database $DB
 * @global type $USER
 * @param type $courseid
 * @return stdClass $license or boolean false
 */
function get_user_course_license($courseid) {
    global $DB, $USER;
    $license = false;
    $matches = array();
    
    $active_licenses = $DB->get_records_sql("
        SELECT *
        FROM   {shopping_basket_license} l
        WHERE  
            l.userid = :userid
        AND
            ( l.enddate IS NULL OR l.enddate > :now )
        ", array('userid' => $USER->id, 'now' => time())
    );
    
    foreach($active_licenses as $l) {
        // Check courses covered by this license
        $product = get_product($l->productid);
        if(array_key_exists($courseid, $product->courses)) {
            // Bingo - course is related to this product
            $matches[] = $l->id;
        }
    }
    
    if(!empty($matches)) {
        // The user has access to the course - now we'll work out
        // the longest enrolment period available to the user
        list($insql, $inparams) = $DB->get_in_or_equal($matches);
        
        $sql = "SELECT l.id, l.startdate, l.enddate
            FROM {shopping_basket_license} l
            WHERE
                l.id $insql
            ORDER BY l.enddate DESC
            LIMIT 1";
        
        $longest_license = $DB->get_record_sql($sql, $inparams);

        if($longest_license) {
            
            $license = $longest_license;
            
            if(!isset($longest_license->enddate)) {
                // No licenses available with an enddate - instead, grab the maximum
                // enrolperiod found for the course across the users purchased products
                $max_sql = "SELECT MAX(p.enrolperiod) enrolperiod
                            FROM {shopping_basket_license} l
                            INNER JOIN {shopping_basket_product} p ON p.id = l.productid
                            WHERE
                                l.id $insql";
                $get_max_enrol = $DB->get_record_sql($max_sql, $inparams);
                if($get_max_enrol) {
                    $license->enrolperiod = $get_max_enrol->enrolperiod;
                }
            }
        }
    }
    
    return $license;
    
}

/**
 * 
 * @global class $CFG
 * @global moodle_database $DB
 * @global type $USER
 * @return type
 */
function shopping_basket_get_billing_details() {
    global $CFG, $DB, $USER;
    require_once $CFG->dirroot . '/user/profile/lib.php';
    
    $output = new stdClass();
    $output->category = '';
    $output->is_valid = false;
    $output->address = null;
    
    $userid = $USER->id;
    // If user is "admin" fields are displayed regardless

    $profilefieldvalues = $DB->get_records('user_info_data', array('userid' => $userid));
    $categoryname = get_config('block_shopping_basket', 'addresscategory');
    
    // TODO - replace with user profile category setting
    if ($categoryname && $categories = $DB->get_records('user_info_category', array('name' => $categoryname), 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid'=>$category->id), 'sortorder ASC')) {
                $output->category = $category->name;
                
                $address = new stdClass();
                // check first if *any* fields will be displayed
                foreach ($fields as $field) {
                    $shortname = $field->shortname;
                    $address->$shortname = null;
                    foreach ($profilefieldvalues as $fieldvalue) {
                        if ($fieldvalue->fieldid == $field->id) {
                            $field->value = $fieldvalue->data;
                        }
                    }
                    
                    if (($field->visible != PROFILE_VISIBLE_NONE) && !empty($field->value)) {
                        $address->$shortname->value = $field->value;
                        $address->$shortname->name = $field->name;
                    }
                } 
                
                $output->address = $address;
                
                foreach ($fields as $field) {
                    if ($field->required && empty($field->value)) {
                        $output->is_valid = false;
                        break;
                    }
                    else {
                        $output->is_valid = true;
                    }
                }
            }
        }
    }
    
    return $output;
}

/**
 * Returns the basket settings URL - differs depending on access rights
 * @return moodle_url
 */
function get_settings_url() {
    if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
        return new moodle_url('/blocks/shopping_basket/admin.php');
    } else {
        return new moodle_url('/admin/settings.php?section=blocksettingshopping_basket');
    }
}

/**
 * Update our shopping basket settings
 * @param object $settings
 * @return boolean
 */
function update_basket_settings($settings) {
    $success = true;
    foreach($settings as $name => $value) {
        $s = set_config($name, $value, 'block_shopping_basket');
        if(!$s) {
            $success = false;
        }
    }
    return $success;
}

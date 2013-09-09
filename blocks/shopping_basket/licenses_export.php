<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->libdir.'/excellib.class.php');

global $USER;

require_login();

if ($USER->username == 'guest') {
    // Prevent Guest users from seeing this page
    print_error('accessdenied', 'admin');
    die;
}

// Get the query string
$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$licenses = get_user_product_licenses($USER->id,$id,$type);
$product = get_product($id);
$max_len = 100;
$workbook = new MoodleExcelWorkbook("-");
$filename = clean_filename(substr($product->fullname, 0, $max_len).'-licenses.xls');
$workbook->send($filename);
$worksheet = array();

$worksheet[0] =& $workbook->add_worksheet('');
$row = 0;

if( $licenses ) {
    
    // Output the header
    $worksheet[0]->write($row, 0, get_string('licensecode', 'block_shopping_basket'));
    $worksheet[0]->write($row, 1, get_string('licensestart', 'block_shopping_basket'));
    $worksheet[0]->write($row, 2, get_string('licenseend', 'block_shopping_basket'));
    if ($type == 'used') {
        // Include assigned user details
        $worksheet[0]->write($row, 3, get_string('licenseuser', 'block_shopping_basket'));
        $worksheet[0]->write($row, 4, get_string('licenseemail', 'block_shopping_basket'));
    }
    $row++;

    // Output the licenses
    foreach ($licenses as $license) {
        $worksheet[0]->write($row, 0, $license->code);
        $worksheet[0]->write($row, 1, $license->startdate ? userdate($license->startdate) : '-');
        $worksheet[0]->write($row, 2, $license->enddate ? userdate($license->enddate) : '-');
        if ($type == 'used') {
            $worksheet[0]->write($row, 3, $license->username ? $license->username : '-');
            $worksheet[0]->write($row, 4, $license->useremail ? $license->useremail : '-');
        }
        $row++;
    }
    
} else {
    // No licenses to export
    $worksheet[0]->write($row, 0, get_string('nolicenses','block_shopping_basket'));
}

// Close the workbook
$workbook->close();

?>

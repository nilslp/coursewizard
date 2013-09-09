<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {   
    // SECTION: Payment Details
    $settings->add(new admin_setting_heading('shopping_basket_payment_header', get_string('settingpaymentdetails', 'block_shopping_basket'), ''));
    
    $payment_provider = array();
    $payment_provider[1] = 'PayPal';
    $payment_provider[2] = 'Sage Pay';
    
    // Payment Provider selection
    $settings->add(new admin_setting_configselect('block_shopping_basket/paymentprovider', get_string('paymentprovider', 'block_shopping_basket'), get_string('paymentprovider_help', 'block_shopping_basket'), 1, $payment_provider));
    
    // PayPalID/Sage Pay Vendor ID account
    $settings->add(new admin_setting_configtext('block_shopping_basket/vendoridentifier', get_string('vendoridentifier', 'block_shopping_basket'), get_string('vendoridentifier_help', 'block_shopping_basket'), ''));
    
    // Sanbox mode
    $settings->add(new admin_setting_configcheckbox('block_shopping_basket/sandboxmode', get_string('sandboxmode', 'block_shopping_basket'), get_string('sandboxmode_help', 'block_shopping_basket'), 0));
    
    $currency_codes = array();
    
    $currency_codes['AUD'] = "Australian Dollar";	
    $currency_codes['BRL'] = "Brazilian Real"; 
    $currency_codes['CAD'] = "Canadian Dollar";
    $currency_codes['CZK'] = "Czech Koruna";
    $currency_codes['DKK'] = "Danish Krone";
    $currency_codes['EUR'] = "Euro";
    $currency_codes['HKD'] = "Hong Kong Dollar";
    $currency_codes['HUF'] = "Hungarian Forint";
    $currency_codes['ILS'] = "Israeli New Sheqel";
    $currency_codes['JPY'] = "Japanese Yen";
    $currency_codes['MYR'] = "Malaysian Ringgit";
    $currency_codes['MXN'] = "Mexican Peso";
    $currency_codes['NOK'] = "Norwegian Krone";
    $currency_codes['NZD'] = "New Zealand Dollar";
    $currency_codes['PHP'] = "Philippine Peso";	
    $currency_codes['PLN'] = "Polish Zloty";
    $currency_codes['GBP'] = "Pound Sterling";
    $currency_codes['SGD'] = "Singapore Dollar";
    $currency_codes['SEK'] = "Swedish Krona";
    $currency_codes['CHF'] = "Swiss Franc";
    $currency_codes['TWD'] = "Taiwan New Dollar";
    $currency_codes['THB'] = "Thai Baht";
    $currency_codes['TRY'] = "Turkish Lira";
    $currency_codes['USD'] = "U.S. Dollar";	
    
    // Currency
    $settings->add(new admin_setting_configselect('block_shopping_basket/currency', get_string('currency', 'block_shopping_basket'), get_string('currency_help', 'block_shopping_basket'), 'GBP', $currency_codes));
    
    // Default site-wide tax ratio
    $settings->add(new admin_setting_configtext('block_shopping_basket/taxrate', get_string('taxrate', 'block_shopping_basket'),
                       get_string('taxrate_help', 'block_shopping_basket'), 0, PARAM_FLOAT));
    
    // E-mail address to receive alerts
    $settings->add(new admin_setting_configtext('block_shopping_basket/alertemail', get_string('alertemail', 'block_shopping_basket'), get_string('alertemail_help', 'block_shopping_basket'), ''));
        
    // SECTION: Basket Settings
    $settings->add(new admin_setting_heading('shopping_basket_settings_header', get_string('settingbasketsettings', 'block_shopping_basket'), ''));
    
    // Enable discounts
    $settings->add(new admin_setting_configcheckbox('block_shopping_basket/enablediscounts', get_string('enablediscounts', 'block_shopping_basket'), get_string('enablediscounts_help', 'block_shopping_basket'), 0));

    // Accept a PO Number
    $settings->add(new admin_setting_configcheckbox('block_shopping_basket/acceptpo', get_string('acceptpo', 'block_shopping_basket'), get_string('acceptpo_help', 'block_shopping_basket'), 0));

    // Enrolment from purchase date
    $settings->add(new admin_setting_configcheckbox('block_shopping_basket/enrolonpurchasedate', get_string('enrolonpurchasedate', 'block_shopping_basket'), get_string('enrolonpurchasedate_help', 'block_shopping_basket'), 0));
    
    // Extended profile field address category
    $settings->add(new admin_setting_configtext('block_shopping_basket/addresscategory', get_string('addresscategory', 'block_shopping_basket'), get_string('addresscategory_help', 'block_shopping_basket'), ''));
    
    // Enable reminder emails
    $settings->add(new admin_setting_configcheckbox('block_shopping_basket/enableexpiryemail', get_string('enableexpiryemail', 'block_shopping_basket'), get_string('enableexpiryemail_help', 'block_shopping_basket'), 1));
    
    // Time threshold to send reminder emails - Defaulted to 5 days
    $days = array();
    for ($i = 1; $i <= 14; $i++) {
        $days[$i*86400] = $i.' days';
    }
    
    $settings->add(new admin_setting_configselect('block_shopping_basket/expirythreshold', get_string('expirythreshold', 'block_shopping_basket'),
        get_string('expirythreshold_help', 'block_shopping_basket'), 432000, $days));
    
    // Define a custom reminder email message
    $settings->add(new admin_setting_confightmleditor('block_shopping_basket/customexpiryemail', get_string('customexpiryemail', 'block_shopping_basket'), get_string('customexpiryemail_help', 'block_shopping_basket'), ''));
    
    // Add a link to allow the user to configure the products
    $settings->add(new admin_setting_heading('block_shopping_basket_addheading', '', 
            html_writer::link(new moodle_url('/blocks/shopping_basket/products.php'), get_string('viewproducts', 'block_shopping_basket')) . ' | ' 
            . html_writer::link(new moodle_url('/blocks/shopping_basket/vouchers.php'), get_string('discountvouchers', 'block_shopping_basket')) . ' | '
            . html_writer::link(new moodle_url('/blocks/shopping_basket/purchase_orders.php'), get_string('purchaseorders', 'block_shopping_basket'))));
}

<?php
global $CFG;
require_once "$CFG->dirroot/lib/formslib.php";
require_once "$CFG->dirroot/blocks/shopping_basket/lib.php";

class block_shopping_basket_admin_form extends moodleform {

    function definition() {
        global $CFG, $DB, $USER;

        $mform = & $this->_form;

        $mform->addElement('header', 'editpaymentsettings', get_string('settingpaymentdetails', 'block_shopping_basket'));
        
        // Payment provider
        $payment_provider = array();
        $payment_provider[1] = 'PayPal';
        $payment_provider[2] = 'Sage Pay';
        
        $mform->addElement('select', 'paymentprovider', get_string('paymentprovider', 'block_shopping_basket'), $payment_provider);
        $mform->addHelpButton('paymentprovider', 'paymentprovider', 'block_shopping_basket');
        
        // Vendor identifier
        $mform->addElement('text', 'vendoridentifier', get_string('vendoridentifier', 'block_shopping_basket'), array('size' => '30'));
        $mform->setType('vendoridentifier', PARAM_TEXT);
        $mform->addRule('vendoridentifier', null, 'required', null, 'client');
        $mform->addHelpButton('vendoridentifier', 'vendoridentifier', 'block_shopping_basket');
        
        // Sandbox mode
        $mform->addElement('advcheckbox', 'sandboxmode', get_string('sandboxmode', 'block_shopping_basket'));
        $mform->addHelpButton('sandboxmode', 'sandboxmode', 'block_shopping_basket');
        
        // Currency
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
        
        $mform->addElement('select', 'currency', get_string('currency', 'block_shopping_basket'), $currency_codes);
        $mform->addHelpButton('currency', 'currency', 'block_shopping_basket');
        
        // Tax ratio
        $mform->addElement('text', 'taxrate', get_string('taxrate', 'block_shopping_basket'), array('size' => '30'));
        $mform->setType('taxrate', PARAM_FLOAT);
        $mform->addRule('taxrate', null, 'numeric', null, 'client');
        $mform->addRule('taxrate', null, 'required', null, 'client');
        $mform->addHelpButton('taxrate', 'taxrate', 'block_shopping_basket');
        $mform->setDefault('taxrate', 0);
        
        //Alert email address
        $mform->addElement('text', 'alertemail', get_string('alertemail', 'block_shopping_basket'), array('size' => '30'));
        $mform->addHelpButton('alertemail', 'alertemail', 'block_shopping_basket');
        $mform->setDefault('alertemail', '');
        
        $mform->addElement('header', 'editbasketsettings', get_string('settingbasketsettings', 'block_shopping_basket'));
        
        // Enable Discount Vouchers
        $mform->addElement('advcheckbox', 'enablediscounts', get_string('enablediscounts', 'block_shopping_basket'));
        $mform->addHelpButton('enablediscounts', 'enablediscounts', 'block_shopping_basket');
        $mform->setDefault('enablediscounts', 0);
        
        // Accept POs
        $mform->addElement('advcheckbox', 'acceptpo', get_string('acceptpo', 'block_shopping_basket'));
        $mform->addHelpButton('acceptpo', 'acceptpo', 'block_shopping_basket');
        $mform->setDefault('acceptpo', 0);
        
        // Enrolments start on purchase date
        $mform->addElement('advcheckbox', 'enrolonpurchasedate', get_string('enrolonpurchasedate', 'block_shopping_basket'));
        $mform->addHelpButton('enrolonpurchasedate', 'enrolonpurchasedate', 'block_shopping_basket');
        $mform->setDefault('enrolonpurchasedate', 0);
        
        // Address Category name
        $mform->addElement('text', 'addresscategory', get_string('addresscategory', 'block_shopping_basket'), array('size' => '30'));
        $mform->addHelpButton('addresscategory', 'addresscategory', 'block_shopping_basket');
        $mform->setDefault('addresscategory', '');
        
        // Enable expiry email
        $mform->addElement('advcheckbox', 'enableexpiryemail', get_string('enableexpiryemail', 'block_shopping_basket'));
        $mform->addHelpButton('enableexpiryemail', 'enableexpiryemail', 'block_shopping_basket');
        $mform->setDefault('enableexpiryemail', 1);
        
        // Enrolment expiry threshold
        // Time threshold to send reminder emails - Defaulted to 5 days
        $days = array();
        for ($i = 1; $i <= 14; $i++) {
            $days[$i*86400] = $i.' days';
        }
        $mform->addElement('select', 'expirythreshold', get_string('expirythreshold', 'block_shopping_basket'), $days);
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'block_shopping_basket');
        $mform->setDefault('expirythreshold', 432000);
        
        // Custom Expiry Message
        $mform->addElement('htmleditor', 'customexpiryemail', get_string('customexpiryemail', 'block_shopping_basket'));
        $mform->addHelpButton('customexpiryemail', 'customexpiryemail', 'block_shopping_basket');
        $mform->setType('customexpiryemail', PARAM_CLEAN);
        $mform->setDefault('customexpiryemail', '');
        
        $links = html_writer::link(new moodle_url('/blocks/shopping_basket/products.php'), get_string('viewproducts', 'block_shopping_basket')) . ' | ' 
            . html_writer::link(new moodle_url('/blocks/shopping_basket/vouchers.php'), get_string('discountvouchers', 'block_shopping_basket')) . ' | '
            . html_writer::link(new moodle_url('/blocks/shopping_basket/purchase_orders.php'), get_string('purchaseorders', 'block_shopping_basket'));
        
        // Links to manage products, etc;
        $mform->addElement('header', '', $links);
        
        if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
            $mform->freeze(array('paymentprovider', 'vendoridentifier', 'sandboxmode', 'addresscategory'));
        }
        
        $this->add_action_buttons();
    }
}

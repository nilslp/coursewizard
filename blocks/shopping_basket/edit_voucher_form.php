<?php
global $CFG;
require_once "$CFG->dirroot/lib/formslib.php";
require_once "$CFG->dirroot/blocks/shopping_basket/lib.php";

class block_shopping_basket_edit_voucher_form extends moodleform {

    function definition() {
        global $CFG, $DB, $USER;

        $mform = & $this->_form;

        $id = $this->_customdata['id'];

        $header = $id == 0 ? get_string('addnewvoucher', 'block_shopping_basket') : get_string('editvoucher', 'block_shopping_basket');
        
        $mform->addElement('header', 'editvoucher', $header);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        
        function check_code_is_unique($value) {
            return is_discountcode_unique($value);
        }
        
        $mform->addElement('text', 'discountcode', ($id == 0 ? get_string('vouchercodelabel', 'block_shopping_basket') : get_string('vouchercode', 'block_shopping_basket')), array('maxlength' => 20, 'style' => 'text-transform: uppercase'));
        $mform->setType('discountcode', PARAM_TEXT);
        
        if ($id != 0) {
            $mform->freeze('discountcode');            
        }
        else {
            $mform->addRule('discountcode', get_string('validationstringalreadyinuse', 'block_shopping_basket', get_string('vouchercode', 'block_shopping_basket')), 'callback', 'check_code_is_unique');
        }
        
        $mform->addElement('radio', 'discounttype', '', get_string('discounttypepercentage', 'block_shopping_basket'), DISCOUNT_TYPE_PERCENTAGE);     
        $mform->addElement('radio', 'discounttype', '', get_string('discounttypefixedorder', 'block_shopping_basket'), DISCOUNT_TYPE_FIXED_ORDER);
        $mform->setDefault('discounttype', DISCOUNT_TYPE_PERCENTAGE);

        function greater_than_zero($value) {
            if (floatval($value) > 0) {
                return true;
            }
            else {
                return false;
            }
        }
        
        $mform->addElement('text', 'rate', get_string('vouchervalue', 'block_shopping_basket'));
        $mform->setType('rate', PARAM_FLOAT);
        $mform->addRule('rate', null, 'numeric', null, 'client');
        $mform->addRule('rate', null, 'required');
        $mform->addRule('rate', get_string('greaterthanzero', 'block_shopping_basket'), 'callback', 'greater_than_zero');
               
        $mform->addElement('text', 'minordervalue', get_string('minordervalue', 'block_shopping_basket'));
        $mform->setType('minordervalue', PARAM_FLOAT);
        $mform->addRule('minordervalue', null, 'numeric', null, 'client');
        $mform->addRule('minordervalue', get_string('greaterthanzero', 'block_shopping_basket'), 'callback', 'greater_than_zero');
        
        $datecomponents = array();
        $datecomponents[] =& $mform->createElement('checkbox', 'enableexpiry', null, '&nbsp;' . get_string('enableexpirationdate', 'block_shopping_basket') . '&nbsp;');
        $datecomponents[] =& $mform->createElement('date_time_selector', 'expirydate', null);
        
       $mform->addElement('group', 'date_group', get_string('expirationdate', 'block_shopping_basket'), $datecomponents, '', false);

        $mform->disabledIf('expirydate[day]', 'enableexpiry', 'notchecked');
        $mform->disabledIf('expirydate[month]', 'enableexpiry', 'notchecked');
        $mform->disabledIf('expirydate[year]', 'enableexpiry', 'notchecked');
        $mform->disabledIf('expirydate[hour]', 'enableexpiry', 'notchecked');
        $mform->disabledIf('expirydate[minute]', 'enableexpiry', 'notchecked');
        
        $mform->addElement('advcheckbox', 'singleuse', '', '&nbsp;' . get_string('singleusevoucher', 'block_shopping_basket'),  null, 1);
        $mform->addElement('advcheckbox', 'maxval', '', '&nbsp;' . get_string('subtotalvalidation', 'block_shopping_basket'), null,  1);
        $mform->setDefault('maxval', true);
        
        $this->add_action_buttons();
    }
}


<?php
/****
 * This file contains custom QuickForm validation rules
 */

class QuickForm_Buyways extends HTML_QuickForm_Rule{
    
    function validate($value) {
        //can't be validated at this point
        return true;
    }
        
    function getValidationScript($options = null) {
        return array("","{jsVar}!=='' && !M.block_buyways.validate({jsVar})");
    }
    
}
?>
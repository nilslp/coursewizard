<?php

/**
 * Javascript file containing JQuery bindings for hierarchy dialog filters
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/learningpool/js/setup.php');
header('Content-Type: text/javascript; charset=utf-8');

?>
// See local/reportbuilder/lib.php method include_js()
// for orgtree variable definitions


// Bind functionality to page on load
$(function() {


    for(i in orgtree) {

        ///
        /// Organisation dialog
        ///
        (function() {
            var url = '<?php echo $CFG->wwwroot ?>/local/learningpool/hierarchy/assign/';

            lpSingleSelectDialog(
                orgtree[i],
                '<?php
                    echo get_string('chooseorganisation', 'local_learningpool');
                    echo dialog_display_currently_selected(get_string('currentlyselected', 'local_learningpool'), '\'+orgtree[i]+\'');
                ?>',
                url+'find.php?',
                orgtree[i],
                orgtree[i] + 'title'
            );

            // disable popup buttons if first pulldown is set to
            // 'any value'
            if($('select[name='+orgtree[i]+'_op]').val() == 0) {
                $('input[name='+orgtree[i]+'_rec]').attr('disabled',true);
                $('#show-'+orgtree[i]+'-dialog').attr('disabled',true);
            }
        })();

    }


});

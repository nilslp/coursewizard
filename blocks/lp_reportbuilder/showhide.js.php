<?php

/**
 * Javascript file containing JQuery bindings for show/hide popup dialog box
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
header('Content-Type: text/javascript; charset=utf-8');

?>
// bind functionality to page on load
$(function() {

    ///
    /// show/hide column dialog
    ///
    (function() {

        // id not set when zero results
        // http://verens.com/2005/07/25/isset-for-javascript/#comment-332
        if(window.id===undefined) {return;}

        $('#show-showhide-dialog').css('display','block');
        var url = '<?php echo $CFG->wwwroot ?>/blocks/lp_reportbuilder/';

        var handler = new lpDialog_handler();
        var name = 'showhide';

        lpDialogs[name] = new lpDialog(
            name,
            'show-'+name+'-dialog',
            {
                buttons: {
                    'Ok': function() { handler._cancel() }
                },
                title: '<h2><?php echo get_string('showhidecolumns', 'block_lp_reportbuilder') ?></h2>'
            },
            url+'showhide.php?id='+id.toString(),
            handler
        );

    })();

});

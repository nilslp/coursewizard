M.local_dlelegacytools = {};

M.local_dlelegacytools.init = function(Y) {
    // Add Overlay Object
    var dle_overlay = new Y.Panel({
        srcNode: '#dle-overlay',
        visible: false,
        width: '65%',
        height: '65%',
        centered: true,
        constrain: true,
        render: true,
        modal: true,
        zIndex: 1100,
        // headerContent: '',
        bodyContent: '<div class="wait">Loading</div>',
        footerContent: '',
        buttons: [
            {
                value: 'Close',
                action: function(e) {
                    e.preventDefault();
                    dle_overlay.hide();
                },
                section: Y.WidgetStdMod.FOOTER,
                classNames: 'close'
            }
        ]
    });

    // Attach an AJAX call to the button click to return users 
   var notice_container = Y.one('div#notice');
   
   notice_container && notice_container.delegate(
            'click',
            function(e) {
                Y.io('/local/dlelegacytools/confirmdelete/userlist.php?mod=' + this.getAttribute('mod') + '&sco=' + this.getAttribute('sco') + "&inst=" + this.getAttribute('inst'), {
                    on:   {success: displayUsersModal}
                });
            },
            'input.view_users_button'
        );
   
    // Define a function to handle the response data.
    function displayUsersModal(id, o, args) {
        dle_overlay.show();
        var data = o.responseText; // Response data.
        var content = Y.one('.yui3-panel-content .yui3-widget-bd');
        if (content) content.setContent(data);
    }
}

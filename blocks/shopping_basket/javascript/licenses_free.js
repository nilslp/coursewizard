M.block_shopping_basket_licenses_free = {};

M.block_shopping_basket_licenses_free.init = function (Y, sesskey, imagepath) {
    var panel = new Y.Panel({
        srcNode: '#panel_container',
        bodyContent: '',
        visible  : false,
        width    : '35%',
        height   : '30%',
        zIndex   : 1500,
        modal    : true,
        centered : true,
        render  : true,
        buttons : [     
            {
                value: "OK",

                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },

                section: Y.WidgetStdMod.FOOTER
            }
        ]
    });
            
    Y.all('.email_icon').on('click', function(e) {
        var currentId = e.currentTarget.get('id');
        var textboxId = currentId.replace('_icon', '');

        var email = Y.one('#' + textboxId).get('value');
        var code = Y.one('#' + currentId.replace('email_icon', 'code')).getAttribute('value');
        email = email.trim();
        
        if (email == '') {
            alert(M.util.get_string('pleaseenteremail', 'block_shopping_basket'));
        }
        else {
            if (confirm(M.util.get_string('confirmlicensesend', 'block_shopping_basket', email))) {
                var text = '<div id="sending"><div id="sending_msg">' + M.util.get_string('sendinglicenseto', 'block_shopping_basket', '<b>' + email +  '</b>');
                text += '<br /><img src="' + imagepath + '"></div></div>';
                panel.set('bodyContent', text);
                panel.show();

                Y.io('/blocks/shopping_basket/ajax/json-proxy.php', {
                    method: "post",
                    data: 'sesskey=' + sesskey + '&email=' + email + '&code=' + code + '&op=send_code',
                    on:   {success: function(transactionId, o, args) {
                            try {
                                json = Y.JSON.parse(o.responseText);

                                if (json['success'] == true) {
                                    panel.set('bodyContent', M.util.get_string('emailsent', 'block_shopping_basket'));
                                }
                                else {
                                    panel.set('bodyContent', M.util.get_string('emailnotsent', 'block_shopping_basket'));
                                }
                            }
                            catch (ex) {
                                panel.hide();
                            }
                        }
                    }
                });
            }
        }
    });
}


M.block_shopping_basket_products = {};

M.block_shopping_basket_products.init = function (Y, sesskey) {
    var panel = new Y.Panel({
        srcNode: Y.Node.create('<div id="panel_container"></div>'),
        bodyContent: M.util.get_string('loading', 'block_shopping_basket'),
        headerContent: '<h2>' + M.util.get_string('copythehtml', 'block_shopping_basket') + '</h2>',
        width        : '30%',
        height       : '45%',
        zIndex       : 1500,
        centered     : true,
        modal        : true,
        visible      : false,
        render       : true,
        buttons: [
            {
                value: M.util.get_string('close', 'block_shopping_basket'),

                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },

                section: Y.WidgetStdMod.FOOTER
            }
        ]
    });
                    
    function display_html(e) {
        var productId = e.currentTarget.get('id').replace('get_product_', '');
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'sesskey=' + sesskey + '&op=get_product_markup&product=' + productId,
            on:   {success: function(transactionId, o, args) {
                    panel.bodyNode.set('innerHTML', o.responseText);
                    var textarea = Y.one('#html_textarea');
                    textarea && textarea.on('focus', function(e) {
                        textarea.select();
                    });
                }
            }
        });
        
        panel.show();    
    }
    
    var body = Y.one('body');
    
    body && body.delegate('click', display_html, 'input[type="button"]'); 
}
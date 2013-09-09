M.block_shopping_basket = {};

M.block_shopping_basket.init = function (Y, sesskey, checkout, enrol) {
    if(typeof(enrol)==='undefined') enrol = false;
    
    var popout = new Y.Panel({
        srcNode : Y.Node.create('<div id="shopping_basket_popup" />'),
        headerContent : '<h3>'+M.util.get_string('licensepurchase_help_title', 'block_shopping_basket')+'</h3>',
        bodyContent : M.util.get_string('licensepurchase_help_body1', 'block_shopping_basket') + '<br />' + '<br />' 
            + M.util.get_string('licensepurchase_help_body2', 'block_shopping_basket') + '<br />' + '<br />' 
            + M.util.get_string('licensepurchase_help_body3', 'block_shopping_basket'),
        width   : 400,
        render  : true,
        centered : true,
        visible : false,
        modal : true,
        zIndex : 1500,
        buttons: [
            {
                value  : M.util.get_string('ok', 'moodle'),
                section: Y.WidgetStdMod.FOOTER,
                action : function (e) {
                    e.preventDefault();
                    popout.hide();
                }
            }
        ]
    });
    
    function refresh_basket(html) {
        var container = Y.one('#basket_container');
        container && container.set('innerHTML', html);
    }
    
    function add(form) {
        var itemId = form.one('input[name="my-item-id"]').get('value');
        var itemName = form.one('input[name="my-item-name"]').get('value');
        var itemPrice = form.one('input[name="my-item-price"]').get('value');
        var itemQuantity = form.one('input[name="my-item-qty"]').get('value');
        
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'type=add&my-add-button=1&my-item-id=' + itemId + '&my-item-name=' + itemName + '&my-item-price=' + itemPrice + '&my-item-qty=' + itemQuantity + '&sesskey=' + sesskey + '&checkout=' + checkout,
            on:   {success: function(transactionId, o, args) {
                    refresh_basket(o.responseText);
                }
            }
        });
    }
    
    function remove(link) {
        var itemId = link.get('id').replace('remove_basket_', '');
        
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'deletebasketitem=' + itemId +'&sesskey=' + sesskey + '&checkout=' + checkout,
            on:   {success: function(transactionId, o, args) {
                    refresh_basket(o.responseText);
                }
            }
        });
    }
    
    function updateQuantity(input) {
        var itemId = input.get('id').replace('item_quantity_', '');
        var quantity = input.get('value');
        
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'updatebasketitem=' + itemId + '&quantity=' + quantity + '&sesskey=' + sesskey + '&checkout=' + checkout,
            on:   {success: function(transactionId, o, args) {
                    refresh_basket(o.responseText);
                }
            }
        });
    }
    
    function enableFormByValue(value) {
        var forms = Y.all('.for_sale');
        
        forms.each(function (form) {
           var itemId = form.one('input[name="my-item-id"]').get('value');
           
           if (itemId == value) {
                var button = form.one('input[name="my-add-button"]');

                button && button.removeAttribute('disabled');
           }
        });
    }
    
    function addDiscount() {
        var vouchercode = Y.one('#vouchercode').get('value');
        
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'op=apply_voucher&vouchercode=' + vouchercode + '&sesskey=' + sesskey + '&checkout=' + checkout,
            on:   {success: function(transactionId, o, args) {
                    refresh_basket(o.responseText);
                }
            }
        });
    }
    
    function handleVoucherKeydown(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            var vouchercode = e.currentTarget.get('value');
        
            Y.io('/blocks/shopping_basket/ajax/proxy.php', {
                method: "post",
                data: 'op=apply_voucher&vouchercode=' + vouchercode + '&sesskey=' + sesskey + '&checkout=' + checkout,
                on:   {success: function(transactionId, o, args) {
                        refresh_basket(o.responseText);
                    }
                }
            });
        }
    }
    
    function removeDiscount(e) {
        e.preventDefault();
        
        Y.io('/blocks/shopping_basket/ajax/proxy.php', {
            method: "post",
            data: 'op=remove_voucher&sesskey=' + sesskey + '&checkout=' + checkout,
            on:   {success: function(transactionId, o, args) {
                    refresh_basket(o.responseText);
                }
            }
        });
    }
    
    function handleAdd(e) {
        e.preventDefault();
        add(e.currentTarget);
    }
    
    function handleRemove(e) {
        e.preventDefault();
        remove(e.currentTarget);
    }

    function handleUpdate(e) {
        e.currentTarget.set('value', removeNonNumerals(e.currentTarget.get('value')));
        updateQuantity(e.currentTarget);
    }
    
    function handleQtyKeydown(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            handleUpdate(e);
        }
    }
    
    function showPopupWindow() {
        popout.show();
    }
    
    function removeNonNumerals(n) {
        // Allow whole numbers and an empty string -
        // user may delete the qty to remove the item from their basket
        n = n.replace(/\s/g,'');
        if (n === '' || isWholeNumber(n)) {
            return n;
        } else {
            // Remove non-numeric characters
            n = n.replace(/\D/g,'');
        }
        if(n.length === 0) {
            // Default to 1 if we only had non-numeric characters added
            n = 1;
        }
        return n;
    }
    
    function isWholeNumber(n) {
        var digitpattern = /^\d+$/;
        return digitpattern.test(n);
    }
    
    function isAlphaNumeric(s) {
        var pattern = /^\w+$/;
        return pattern.test(s);
    }
    
    function handlePOSubmit(e) {
        var po_number = Y.one('#po_number');
        var form = Y.one('#checkout_form');

        if (!isAlphaNumeric(po_number.get('value').trim())) {
            alert('Please enter a valid PO number. Special characters are not permitted');
            e.preventDefault();
            return false;
        }
        form.setAttribute('action', 'process.php');
        return true;
    }
    
    function handlePOKeydown(e) {
        if (e.keyCode == 13) {
            e.stopPropagation();
            e.preventDefault();
            return false;
        }
    }
    
    // Wire up the AJAX handlers        
    Y.all('.for_sale').on('submit', handleAdd);
    
    // Delegate the removal of shopping basket items
    var block = Y.one('#basket_container');
    block && block.delegate('click', handleRemove, 'a.remove_basket_item');
    block && block.delegate('blur', handleUpdate, 'input.item_quantity');
    block && block.delegate('keydown', handleQtyKeydown, 'input.item_quantity');
    
    var update_button = Y.one('#update_basket_quantity');

    update_button && update_button.hide();
    
    block && block.delegate('click', addDiscount, 'input.add_discount');
    block && block.delegate('click', removeDiscount, 'a.remove_discount');
    block && block.delegate('keydown', handleVoucherKeydown, '#vouchercode');
    
    // Help popups
    block.delegate('click', showPopupWindow, 'a.shopping_basket_help');
    
    if (checkout) {
        block && block.delegate('click', handlePOSubmit, '#submit_po');
        block && block.delegate('keydown', handlePOKeydown, 'input#po_number');
    }
}
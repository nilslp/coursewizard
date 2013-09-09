M.local_learningpool_postcodelookup = {};

M.local_learningpool_postcodelookup.init = function(Y, fields) {
    var premiseDialog = false, fieldNodes = {}, premiseSelect = false, currentData = false;
    var lookupBtn = Y.Node.create('<input name="postcode_lookupBtn" value="'+M.util.get_string('findaddress', 'local_learningpool')+'" type="button" id="id_postcode_lookupBtn" />');
    
    var region_main = Y.one('#region-main');
    region_main && region_main.append(Y.Node.create('<div id="id_premiseDialog"></div>'));
    
    // first, hide all the address fields
    fieldNodes.postcode = Y.one('#' + fields.postcode);
    fieldNodes.town = Y.one('#' + fields.town);
    prepFieldNode(fieldNodes.town);
    fieldNodes.country = Y.one('#' + fields.country);
    prepFieldNode(fieldNodes.country);
    fieldNodes.address1 = Y.one('#' + fields.address1);
    prepFieldNode(fieldNodes.address1);
    fieldNodes.address2 = Y.one('#' + fields.address2);
    prepFieldNode(fieldNodes.address2);
    fieldNodes.address3 = Y.one('#' + fields.address3);
    prepFieldNode(fieldNodes.address3);
    fieldNodes.address4 = Y.one('#' + fields.address4);
    prepFieldNode(fieldNodes.address4);
    
    
    // callback to populate the fields
    var callback = {
        // don't wait any longer than this
        timeout : 5000,
        // handlers
        on : {
            success : function (x,o) {
                // Process the JSON data returned from the server
                try {
                    result = Y.JSON.parse(o.responseText);
                }
                catch (e) {
                    if (fields.debug) {
                        alert(M.util.get_string('error:generic', 'local_learningpool') + "\n\nError:\n\n" + e.message);    
                    } else {
                        alert(M.util.get_string('error:generic', 'local_learningpool'));                            
                    }
                    return;
                }
                    
                showPremiseDialog(result);
            },

            failure : function (x,o) {
                if (fields.debug) {
                    alert(M.util.get_string('error:generic', 'local_learningpool') + "\n\nError:\n\nSomething went wrong with ajax call.");    
                } else {
                    alert(M.util.get_string('error:generic', 'local_learningpool'));                            
                }
            }
        }
    };
    
    // configure premise dialog
    premiseDialog = new Y.Panel({
        srcNode      : '#id_premiseDialog',
        headerContent: "<h3>" + M.util.get_string('chooseanaddress', 'local_learningpool') + "</h3>",
        bodyContent  : '<div class="postcodelookup searching"></div>',
        zIndex       : 5,
        centered     : true,
        modal        : true,
        visible      : false,
        render       : true, 
        buttons: [
            {
                value: M.util.get_string('ok', 'moodle'),
                action: function(e) {
                    e.preventDefault();        
                    premiseDialog.hide();
                    var index = premiseSelect.get('selectedIndex');
                    var options = premiseSelect.get('options');
                    var selectedItem = options.item(index).get('value');
                    if (selectedItem) {
                        currentData.address1 = selectedItem;
                        populateForm(currentData);
                    }
                },
                section: Y.WidgetStdMod.FOOTER                
            } ,
            {
            
                value: M.util.get_string('cancel','moodle'),
                action: function(e) {
                    e.preventDefault();
                    premiseDialog.hide();
                },
                section: Y.WidgetStdMod.FOOTER                
            }
        ]
    });

    function showPremiseDialog(data) {
        if (premiseDialog) {            
            premiseDialog.show();
            if (data) {
                if (data.success) {
                    currentData = data;
                    // do data thing
                    premiseSelect = [
                        '<select id="postcodelookup_premise_selector">'
                    ];
                    for (var i = 0; i < currentData.options.length; ++i) {
                        premiseSelect.push('<option value="'+currentData.options[i]+'">'+currentData.options[i]+'</option>');
                    }
                    premiseSelect.push('</select>');
                    premiseSelect = Y.Node.create(premiseSelect.join(''));
                    premiseDialog.setStdModContent(Y.WidgetStdMod.BODY, premiseSelect);
                } else {
                    premiseDialog.setStdModContent(Y.WidgetStdMod.BODY, '<div class="postcodelookup error">' + data.msg + '</div>');                    
                }
            } else {
                premiseDialog.setStdModContent(Y.WidgetStdMod.BODY, '<div class="postcodelookup searching">Searching ...</div>');
            }
        }
    }
    
    function doLookup(e) {
        e.preventDefault();
        var postcode = fieldNodes.postcode.get('value');
        if (postcode) {
            Y.io(fields.lookupurl + '?postcode=' + postcode, callback);
            showPremiseDialog(null);
        } else {
            alert(M.util.get_string('error:mustprovidepostcode', 'local_learningpool'));
        }
    }
    
    function populateForm(data) {
        if (fieldNodes && fields && data) {
            if (fieldNodes.town && data.town) {
                fieldNodes.town.set('value', data.town);
                fieldNodes.town.ancestor('div.fitem').setStyle('display', 'block');
            } else {
                fieldNodes.town && fieldNodes.town.ancestor('div.fitem').setStyle('display', 'none');                
            }
            
            if (fieldNodes.address1 && data.address1) {
                fieldNodes.address1.set('value', data.address1);
                fieldNodes.address1.ancestor('div.fitem').setStyle('display', 'block');
            } else {
                fieldNodes.address1 && fieldNodes.address1.ancestor('div.fitem').setStyle('display', 'none');                
            }
            
            if (fieldNodes.address2 && data.address2) {
                fieldNodes.address2.set('value', data.address2);
                fieldNodes.address2.ancestor('div.fitem').setStyle('display', 'block');
            } else {
                fieldNodes.address2 && fieldNodes.address2.ancestor('div.fitem').setStyle('display', 'none');                
            }      
            
            if (fieldNodes.address3 && data.address3) {
                fieldNodes.address3.set('value', data.address3);
                fieldNodes.address3.ancestor('div.fitem').setStyle('display', 'block');
            } else {
                fieldNodes.address3 && fieldNodes.address3.ancestor('div.fitem').setStyle('display', 'none');                
            }          
            
            if (fieldNodes.address4 && data.address4) {
                fieldNodes.address4.set('value', data.address4);
                fieldNodes.address4.ancestor('div.fitem').setStyle('display', 'block');
            } else {
                fieldNodes.address4 && fieldNodes.address4.ancestor('div.fitem').setStyle('display', 'none');                
            }        
        }
    }
    
    function prepFieldNode(node) {
        if(node && !node.get('value')) {
            node.ancestor('div.fitem').setStyle('display', 'none');
        }
    }    
    
    // first, add a button beside the postcode field for triggering lookups
    if (fieldNodes.postcode) {
        fieldNodes.postcode.insert(lookupBtn, 'after');
        fieldNodes.postcode.on(
            'key',
            doLookup,
            'enter'
        );
        // bind lookup 
        lookupBtn.on(
            'click', 
            doLookup
        );
    }
}

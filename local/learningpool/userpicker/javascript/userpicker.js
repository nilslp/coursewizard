M.local_learningpool_userpicker = {};
 
M.local_learningpool_userpicker.init = function(Y, buttonId, divId, valueTargetId, labelTargetId) { 
    var rowSelected = false;
    var usertable = false;
    var searchdialog = new Y.Panel({
        srcNode  : '#' + divId,
        visible  : false,
        width    : "450px",
        height   : "auto",
        zIndex   : 99,
        modal    : true,
        centered : true
//        headerContent : '<h3>Select a user</h3>' 
    });
    searchdialog.plug(Y.Plugin.Drag, { handles: ['.yui3-widget-hd'] });
    searchdialog.addButton({
        value  : M.util.get_string('confirm','moodle'),
        section: Y.WidgetStdMod.FOOTER,
        action : function (e) {
             if (rowSelected) {
                var input = Y.one('#' + valueTargetId);
                var radio = Y.one('input[name="selecteduser"]:checked');
                input && input.set('value',(radio ? radio.get('value'):''));
                var display = Y.one('#' + labelTargetId);
                display && display.setContent(rowSelected.getValue('firstname') + ' ' + rowSelected.getValue('lastname'));
            }
            searchdialog && searchdialog.hide();
        }
    });
    searchdialog.addButton({
        value  : M.util.get_string('cancel','moodle'),
        section: Y.WidgetStdMod.FOOTER,
        action : function (e) {
            searchdialog && searchdialog.hide();
        }
    });
    initAjaxHandler(searchdialog.get('srcNode').one('form'));
    searchdialog.render();    
    
    var findbtn = Y.one('#' + buttonId);
    findbtn && findbtn.on('click',function(e){
        searchdialog && searchdialog.show();
    });
    
    function initAjaxHandler(form) {
        form && form.on('submit', function(e){
            e.preventDefault();
            Y.io(
                M.cfg.wwwroot+'/local/learningpool/userpicker/ajax/proxy.php',
                {
                    'method' : 'POST',
                    'form' : { 'id' : form.get('id') },
                    'on' : { 
                            'success' : function (tid, o, args) {
                                buildSearchResults('#searchresults',Y.JSON.parse(o.responseText));
                        }
                    }
                }
            );
        });
    }    
    
    function buildSearchResults ( nodeid, results ) {                
        if (!usertable) {
            usertable = new Y.DataTable.Base({
                width : '100%',
                height: "100%",
                scrollable: "y",
                columnset : ['id','firstname','lastname','email']
            });
            
            usertable.plug(Y.Plugin.DataTableScroll, {height: "200px",width:"100%"});
            
            usertable.delegate(
                'click', 
                function (e) {
                    rowSelected = usertable.get('recordset').getRecord(this.get('id'));                    
                    var cells = Y.all('.yui3-datatable-data tr td');
                    cells && cells.removeClass('yui3-datatable-selected');
                    this.all('td').addClass('yui3-datatable-selected');
                    this.one('td input[name="selecteduser"]').set('checked','checked');
                }, 
                '.yui3-datatable-data tr'
            );
            usertable.render(nodeid);
        }
        
        var tablenode = Y.one(nodeid);
        var msgnode = tablenode && tablenode.one('tbody.yui3-datatable-msg');
        var msg = Y.Node.create( '<tr><td colspan="4"><span class="msg">'+M.util.get_string('nouserresults','facetoface')+'</span></td></tr>' );
  
        if (results.users) {
            msg = '';
            for (var i = 0; i < results.users.length; ++i) {
                results.users[i].id = '<input type="radio" name="selecteduser" value="'+results.users[i].id+'" />';
            }
        }
  
        usertable.set('recordset', new Y.Recordset({ records:results.users }));     
        msgnode && msgnode.setContent(msg);
    }
};
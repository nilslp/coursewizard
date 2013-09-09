M.local_moderngovernor_ajax = {};

M.local_moderngovernor_ajax.init = function(Y, columns, pageSize, defaultSortColumn) {
    // Set up the data table for ajax
    var YAHOO = Y.YUI2;
    var selectedschools = {};
    var schemafields = [];
    var payload = {};    
    var searchform = Y.one('#school-search-form');
    var adminform = Y.one('#school-admin-form');
    var search = Y.one('#id_search');
    var lea = Y.one('#id_lea');
    var status = Y.one('#id_status');
    var table = Y.one('#schools-table');
    var combinelist = Y.one('#combine-list');
    var combine = Y.one('#id_combine');
    var combinenum = false;
    var searchInterval = 300;
    var searchTimer = false;
    var cpanel = {};
    var paging = {
        'sort' : defaultSortColumn,
        'dir' : 'ASC',
        'page' : 1,
        'pagesize' : pageSize
    };
    
    // hide the combine form
    cpanel = new Y.Panel({
        srcNode      : '#combine-panel',
        headerContent: "<h3>" + M.util.get_string('combineschools', 'local_moderngovernor') + "</h3>",
        zIndex       : 5,
        centered     : true,
        modal        : true,
        visible      : false,
        render       : true, 
        buttons: [
            {
                value: M.util.get_string('combine', 'local_moderngovernor'),
                action: function(e) {
                    e.preventDefault();        
                    var schoolids = getSelectedSchoolIds();
                    var newnamenode = Y.one('#id_newname');
                    var newleanode = Y.one('#id_newlea');
                    var newname = newnamenode && newnamenode.get('value');
                    var newlea = newleanode && parseInt(newleanode.get('value'),10);
                    if (!(schoolids && schoolids.length >= 2)) {
                        alert(M.util.get_string('notenoughschools','local_moderngovernor'));
                        return;
                    } 
                    
                    if (!(newname && newname.length)) {
                        alert(M.util.get_string('noschoolnamespecified','local_moderngovernor'));
                        return;
                    }
                    
                    if (!newlea || isNaN(newlea)) {
                        alert(M.util.get_string('nonewleaspecified', 'local_moderngovernor'));
                        return;
                    }
                    
                    if (confirm(M.util.get_string('confirmcombine', 'local_moderngovernor', getSelectedSchoolNames()))) {
                        var params = [
                            "action=combine",
                            "newlea=" + newlea,
                            "newname=" + newname,
                            'schoolids=' + schoolids.join(','),
                            'lea='+(lea ? lea.get('value') : ''),
                            'search='+(search ? search.get('value') : ''),
                            'status='  + (status ? status.get('value') : -1)
                        ].join('&');
                        
                        // reset the form and combine data before sending request
                        newnamenode.set('value','');
                        newleanode.set('selectedIndex',0);
                        selectedschools = {};
                        combinelist.setContent(M.util.get_string('noschoolsselected', 'local_moderngovernor'));
                        updateCombineCount();
                        
                        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
                        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
                    }
                    cpanel.hide();
                },
                section: Y.WidgetStdMod.FOOTER                
            } ,
            {
            
                value: M.util.get_string('cancel','moodle'),
                action: function(e) {
                    e.preventDefault();
                    cpanel.hide();
                },
                section: Y.WidgetStdMod.FOOTER                
            }
        ]
    });
    
    // Build the column headers
    for (var column in columns) {
        if (!columns.hasOwnProperty(column)) {
            continue;
        }
        
        columns[column].resizeable = true;    
        schemafields.push(columns[column].key);
    }
        
    var generateRequest = function(oState, oSelf) { 
        // Get states or use defaults 
        oState = oState || {pagination: null, sortedBy: null}; 
        paging.sort = oState.sortedBy ? oState.sortedBy.key : defaultSortColumn; 
        paging.dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC"; 
        paging.page = (oState.pagination) ? oState.pagination.page : 1; 
        paging.pagesize = (oState.pagination) ? oState.pagination.rowsPerPage : pageSize;  
        
        var params = [
            "action=get",
            "sort="    + paging.sort, 
            "sortdir=" + paging.dir, 
            "page="    + paging.page,
            "perpage=" + paging.pagesize,
            'lea='     + (lea ? lea.get('value') : ''),
            'search='  + (search ? search.get('value') : ''),
            'status='  + (status ? status.get('value') : -1)
        ].join('&');
        
        // Build custom request 
        return  params;
    }; 

    var datasource = new YAHOO.util.DataSource("/local/moderngovernor/ajax/admin.php?"); 
 
    datasource.responseType = YAHOO.util.DataSource.TYPE_JSON; 
    datasource.responseSchema = { 
        'fields'        : schemafields,
        'resultsList'   : "data", 
        metaFields    : { 
                            totalRecords: "total", 
                            startIndex: "start"    
                          } 
    }; 

    // DataTable configuration 
    var datatableConfigs = { 
        MSG_EMPTY       : M.util.get_string('noresults', 'local_moderngovernor'),
        MSG_LOADING     : M.util.get_string('loading', 'local_moderngovernor'),
        generateRequest : generateRequest, 
        initialRequest  : generateRequest(), 
        dynamicData     : true,                              
        sortedBy        : {key: defaultSortColumn, dir:YAHOO.widget.DataTable.CLASS_ASC}, 
        paginator       : new YAHOO.widget.Paginator({rowsPerPage: pageSize})
    }; 

    var dataTable = new YAHOO.widget.ScrollingDataTable("schools-table", columns, datasource, datatableConfigs);

    // Update totalRecords on the fly with values from server 
    dataTable.doBeforeLoadData = function(oRequest, oResponse, oPayload) { 
        oPayload.totalRecords = oResponse.meta.totalRecords; 
        oPayload.pagination.recordOffset = oResponse.meta.startIndex; 
        payload = oPayload;
        return oPayload; 
    };
        
    // Set up the search form
    function resendRequest (e) {
        e && e.preventDefault();
        var params = [
            "action=get",
            'lea='+(lea ? lea.get('value') : ''),
            'search='+(search ? search.get('value') : ''),
            'status='  + (status ? status.get('value') : -1)
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
    
    // combination stuff
    function combineSelected(e) {
        e && e.preventDefault();
    }
    
    searchform && searchform.on(
        'submit',
        resendRequest
    ); 
        
    adminform && adminform.on(  
        'submit',
        combineSelected
    );
        
    combine && combine.on(
        'click',
        function (e) {
            cpanel.show();
        }
    );
    
    lea && lea.on(
        'change',
        resendRequest,
        searchform
    );
        
    status && status.on(
        'change',
        resendRequest,
        searchform
    );
    
    search && search.on(
        'keyup',
        function (e) {
            // reset timer
            searchTimer && searchTimer.cancel();
            
            // after a small interval, submit the search
            searchTimer = Y.later(searchInterval, searchform, resendRequest);
        }
    );
        
    // listen to selected schools - no worky in IE7
    table && table.delegate(
        'change',
        function (e) {
            var node = e.currentTarget;
            var sid = node.get('id');
            if (node.get('checked')) {
                selectedschools[sid] = {};
                if (!combinelist.one('.combine-me')) {
                    combinelist.setContent('');
                }
                combinelist.append(createCombineItem(sid));
            } else {
                if (selectedschools.hasOwnProperty(sid)) {
                    delete selectedschools[sid];
                }               
               var child = combinelist.one('#combine-'+sid);
               child && combinelist.removeChild(child);         
                
                if (!combinelist.one('.combine-me')) {
                    combinenum.remove(true);
                    combinenum = false;
                    combinelist.setContent(M.util.get_string('noschoolsselected', 'local_moderngovernor'));
                }
            }      
            
            updateCombineCount();
        },
        'input[type="checkbox"]'
    );
        
         
    // listen to selected schools - no worky in IE7
    table && table.delegate(
        'click',
        function (e) {
            var anchor = e.currentTarget;
            if (!anchor) {
                return;
            }
            
            var tr = anchor.ancestor('tr');
            var schoolName = tr && tr.one('.school').getContent();
            var sid = anchor.get('id').replace('update_sch_','');            
            if (anchor.hasClass('enabled')) {
                // disable school
                if (confirm(M.util.get_string('confirmdisable', 'local_moderngovernor', schoolName))) {
                    updateSchool(sid, 'toggleschool');
                }
            } else {
                // enable school                
                if (confirm(M.util.get_string('confirmenable', 'local_moderngovernor', schoolName))) {
                    updateSchool(sid, 'toggleschool');
                }
            }
        },
        'a.toggleschool'
    );
        
    combinelist && combinelist.delegate(
        'change',
        function (e) {
            var node = e.currentTarget;
            var rid = node.get('id');
            rid = rid.replace('comb_','');
            
            // delete from selection
            if (selectedschools.hasOwnProperty(rid)) {
                delete selectedschools[rid];                
            }          
            
            // unselect original element if on page
            var cb = Y.one('#'+rid);
            cb && cb.set('checked',null);
            
            // remove the input from the combine list
            node.ancestor('div.combine-me').remove(true);
            
            // update list appearance
            if (!combinelist.one('.combine-me')) {
                combinelist.setContent(M.util.get_string('noschoolsselected', 'local_moderngovernor'));
            }
            
            updateCombineCount();
        },
        'input[type="checkbox"]'
    );
        
    // this function and the following event listener allows us to 
    // update which schools have been selected despite the ajax calls
    function reselectOnLoad(e) {
        for (var sid in selectedschools) {
            if (!selectedschools.hasOwnProperty(sid)) {
                continue;
            }
            
            var cb = Y.one('#'+sid);
            cb && cb.set('checked','checked');
        }
    }
    
    dataTable.subscribe(
        'postRenderEvent',
        function (e) {
            reselectOnLoad(e);
        }
    );
        
    function createCombineItem(sid) {
        var node = Y.one('#'+sid);
        var tr = node.ancestor('tr');
        var schoolName = tr && tr.one('.school').getContent();
        var leaName = tr && tr.one('.lea').getContent();
        var container = Y.Node.create('<div id="combine-'+sid+'" class="combine-me"></div>');
        var cbid = "comb_"+sid;
        var cb = Y.Node.create('<input type="checkbox" checked="checked" name="'+cbid+'" id="'+cbid+'" />');
        var title = Y.Node.create('<span class="school-name">'+schoolName+' [ '+leaName+' ]</span>');
        container.append(cb);
        container.append(title);
        
        return container;
    }
    
    function updateCombineCount() {        
        var nodelist = combinelist.all('.combine-me');
        var num =  nodelist ? nodelist.size() : false;

        if (!combinenum) {
            combinenum = Y.Node.create("<span>"+M.util.get_string('combinex', 'local_moderngovernor', 0)+"</span>");
            combine.get('parentNode').append(combinenum);
        }

        combinenum.setContent(M.util.get_string('combinex','local_moderngovernor',num));   
    }
    
    function getSelectedSchoolNames() {
        var ret = [];
        var list = combinelist.all('div.combine-me span.school-name'); 
        for (var i = 0; i < list.size(); ++i) {
            ret.push(list.item(i).getContent());
        }
        
        return ret.join('\n');
    }
    
    function getSelectedSchoolIds() {
        var ret = [];
        var list = combinelist.all('div.combine-me input[type="checkbox"]'); 
        for (var i = 0; i < list.size(); ++i) {
            ret.push(list.item(i).get('id').replace('comb_sch_',''));
        }
        
        return ret;
    }
        
    function updateSchool (sid,actiontype) {
        if (!actiontype) {
            return;
        }
        
        var params = [
            "action=" + actiontype,
            "sort="    + paging.sort, 
            "sortdir=" + paging.dir, 
            "page="    + paging.page,
            "perpage=" + paging.pagesize,
            'school=' + sid,
            'lea='+(lea ? lea.get('value') : ''),
            'search='+(search ? search.get('value') : ''),
            'status='  + (status ? status.get('value') : -1)
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
        
}

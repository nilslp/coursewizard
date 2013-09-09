M.local_welcome_email_ajax = {};

M.local_welcome_email_ajax.init = function(Y, columns, pageSize, defaultSortColumn) {
    // Set up the data table for ajax
    var YAHOO = Y.YUI2;
    var selectedusers = {};
    var schemafields = [];
    var payload = {};    
    var searchform = Y.one('#user-search-form');
    var search = Y.one('#id_search');
    var status = Y.one('#id_status');
    var submit = Y.one('#id_submitbutton');
    var table = Y.one('#users-table');
    var searchInterval = 300;
    var searchTimer = false;
    
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
        var sort = oState.sortedBy ? oState.sortedBy.key : defaultSortColumn; 
        var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC"; 
        var page = (oState.pagination) ? oState.pagination.page : 1; 
        var results = (oState.pagination) ? oState.pagination.rowsPerPage : pageSize;  
        
        var params = [
            "action=get",
            "sort="    + sort, 
            "sortdir=" + dir, 
            "page="    + page,
            "perpage=" + results,
            'search='  + (search ? search.get('value') : ''),
            'status='  + (status ? status.get('value') : -1)
        ].join('&');
        
        // Build custom request 
        return  params;
    }; 

    var datasource = new YAHOO.util.DataSource("/local/welcome_email/ajax/admin.php?"); 
 
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
        MSG_EMPTY       : M.util.get_string('noresults', 'local_welcome_email'),
        MSG_LOADING     : M.util.get_string('loading', 'local_welcome_email'),
        generateRequest : generateRequest, 
        initialRequest  : generateRequest(), 
        dynamicData     : true,                              
        sortedBy        : {key: defaultSortColumn, dir:YAHOO.widget.DataTable.CLASS_ASC}, 
        paginator       : new YAHOO.widget.Paginator({rowsPerPage: pageSize})
    }; 

    var dataTable = new YAHOO.widget.ScrollingDataTable("users-table", columns, datasource, datatableConfigs);

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
            'search='+(search ? search.get('value') : ''),
            'status='  + (status ? status.get('value') : -1)
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_welcome_email'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
    
    searchform && searchform.on(
        'submit',        
        function (e) {
            e.preventDefault();
            return false;
        }
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
        
    function updateSelectedCount() {
        var ids = [];
        for (var sid in selectedusers) {
            if (!selectedusers.hasOwnProperty(sid)) {
                continue;
            }
            ids.push(sid.replace('cb_',''));
        }
        
        var form = Y.one('div.resetstatus form');
        if (form) {
            var target = form.one('label');
            if (target) {
                var str = M.util.get_string('withxselecteduserssetstatus', 'local_welcome_email', ids.length);
                target.setContent(str);
            }
            
            var resetlist = form.one('input#resetlist');
            if (!resetlist) {
                resetlist = Y.Node.create('<input type="hidden" id="resetlist" name="resetlist" value="" />');
                form.append(resetlist);
            }
            
            resetlist.set('value', ids.join(','));
        }
    }
        
    // listen to selected schools - no worky in IE7
    table && table.delegate(
        'change',
        function (e) {
            var node = e.currentTarget;
            var sid = node.get('id');
            if (node.get('checked')) {
                selectedusers[sid] = {};
            } else {
                if (selectedusers.hasOwnProperty(sid)) {
                    delete selectedusers[sid];
                }      
            }     
            updateSelectedCount();
        },
        'input[type="checkbox"]'
    );
                
    // this function and the following event listener allows us to 
    // update which schools have been selected despite the ajax calls
    function reselectOnLoad(e) {
        for (var sid in selectedusers) {
            if (!selectedusers.hasOwnProperty(sid)) {
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
        
    submit && submit.on(
        'click',
        function (e) {
            document.getElementById('user-search-form').submit();
        }
    );
        
//    function updateSchool (sid,actiontype) {
//        if (!actiontype) {
//            return;
//        }
//        
//        var params = [
//            "action=" + actiontype,
//            'school=' + sid,
//            'lea='+(lea ? lea.get('value') : ''),
//            'search='+(search ? search.get('value') : ''),
//            'status='  + (status ? status.get('value') : -1)
//        ].join('&');
//        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
//        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
//    }
        
}

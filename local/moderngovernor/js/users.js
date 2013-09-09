M.local_moderngovernor_users = {};

M.local_moderngovernor_users.init = function(Y, columns, pageSize, defaultSortColumn) {
    // Set up the data table for ajax
    var YAHOO = Y.YUI2;
    var schemafields = [];
    var payload = {};    
    var searchform = Y.one('#user-search-form');
    var search = Y.one('#id_search');
    var status = Y.one('#id_status');
    var table = Y.one('#users-table');
    var searchInterval = 300;
    var searchTimer = false;
    var paging = {
        'sort' : defaultSortColumn,
        'dir' : 'ASC',
        'page' : 1,
        'pagesize' : pageSize
    };
    
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
            "sort="    + paging.sort, 
            "sortdir=" + paging.dir, 
            "page="    + paging.page,
            "perpage=" + paging.pagesize,
            'search='  + (search ? search.get('value') : ''), 
            "status="+(status ? status.get('value') : 0 )
        ].join('&');
        
        // Build custom request 
        return  params;
    }; 

    var datasource = new YAHOO.util.DataSource("/local/moderngovernor/ajax/confirm.php?"); 
 
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
            'search='+(search ? search.get('value') : ''), 
            "status="+(status ? status.get('value') : 0 )
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
        
    searchform && searchform.on(
        'submit',
        resendRequest
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
    
    status && status.on(
        'change',
        resendRequest
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
            var uid = false;
            var useremail = tr && tr.one('.email').getContent();
            if (anchor.hasClass('confirm')) {
                uid = anchor.get('id').replace('confirm_','');      
                if (confirm(M.util.get_string('confirmconfirm', 'local_moderngovernor', useremail))) {
                    confirmUser(uid);
                }
            } else if (anchor.hasClass('reset')) {
                uid = anchor.get('id').replace('reset_','');      
                if (confirm(M.util.get_string('confirmreset', 'local_moderngovernor', useremail))) {
                    resetUser(uid);
                }                
            }
        },
        'a.useraction'
    );
        
    function confirmUser (uid) {
        var params = [
            "userid=" + uid,
            "confirm=1",
            "sort="    + paging.sort, 
            "sortdir=" + paging.dir, 
            "page="    + paging.page,
            "perpage=" + paging.pagesize,
            'search='  + (search ? search.get('value') : ''), 
            "status="+(status ? status.get('value') : 0 )
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
    
    function resetUser (uid) {
        var params = [
            "userid=" + uid,
            "reset=1",
            "sort="    + paging.sort, 
            "sortdir=" + paging.dir, 
            "page="    + paging.page,
            "perpage=" + paging.pagesize,
            'search='  + (search ? search.get('value') : ''), 
            "status="+(status ? status.get('value') : 0 )
        ].join('&');
        dataTable.showTableMessage(M.util.get_string('loading', 'local_moderngovernor'));
        dataTable.getDataSource().sendRequest(params,{success: dataTable.onDataReturnInitializeTable, scope: dataTable, argument:payload});
    }
        
}

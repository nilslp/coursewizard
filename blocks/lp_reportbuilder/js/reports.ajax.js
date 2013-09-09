M.block_lp_reportbuilder_ajax = {};

M.block_lp_reportbuilder_ajax.init = function(Y, reportId, columns, pageSize, defaultSortColumn, params, filtering, wwwroot) {
    var YAHOO = Y.YUI2;
    var columnDefs = [];
    var fields = [];
    var filterForm = Y.one('#report_filter_form');

    if (filtering) {
        filterForm.setStyle('display', 'block');
    }
    
    Y.one('#show-hide-search-filter').on('click', function(e) {
        if (Y.all('#report_filter_form #newfilterreports').size() > 0){
            if (filterForm.getStyle('display') == 'none') {
                filterForm.setStyle('display', 'block');
            } else {
                filterForm.setStyle('display', 'none');                
            }
        } else {
            alert('No filters have been set for this report!');
        }
    });
    
    // Build the column headers
    for (var column in columns) {
        if (!columns.hasOwnProperty(column)) {
            continue;
        }
        var obj = columns[column];
        if (parseInt(obj.hidden,10)) {
            continue;
        }
        
        newColumn = new Object();
        newColumn.key = obj.type + '_' + obj.value;
        newColumn.label = obj.heading;
        newColumn.sortable = !obj.nosort;
        newColumn.resizeable = true;
        
        columnDefs.push(newColumn);        
        fields.push(newColumn.key);
    }
        
    // validate default sort column:
    var temp = defaultSortColumn;
    defaultSortColumn = columnDefs[0] && columnDefs[0].key;
    for (var i = 0; i < columnDefs.length; ++i) {
        if (columnDefs[i].key == temp) {
            defaultSortColumn = temp;
            break;
        }
    }
        
    var generateRequest = function(oState, oSelf) { 
        // Get states or use defaults 
        oState = oState || {pagination: null, sortedBy: null}; 
        var sort = (oState.sortedBy) ? oState.sortedBy.key : defaultSortColumn; 
        var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc"; 
        var startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0; 
        var results = (oState.pagination) ? oState.pagination.rowsPerPage : pageSize; 

        // Set the sort parameter
        if (oState.sortedBy !== null) {
            for (var column in columns) {
                var obj = columns[column];                
                if (parseInt(obj.hidden,10)) { // ignore hidden columns
                    continue;
                }

                if (obj.value == oState.sortedBy.key) {
                    sort = obj.type + '_' + sort;
                    break;
                }
            }
        }
        
        // Build custom request 
        return  "&sort=" + sort + 
                "&sortdir=" + dir + 
                "&spage=" + startIndex + 
                "&perpage=" + results + '&' + params;
    }; 

    var reportsDataSource = new YAHOO.util.DataSource(wwwroot + "/blocks/lp_reportbuilder/ajax/report.php?id=" + reportId); 
 
    reportsDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON; 
    reportsDataSource.responseSchema.fields = fields;
    reportsDataSource.responseSchema = { 
        resultsList: "records", 
	// Access to values in the server response 
	metaFields: { 
            totalRecords: "total", 
	    startIndex: "start"    
        } 
    }; 

    // DataTable configuration 
    var datatableConfigs = { 
        MSG_EMPTY: M.util.get_string('noresultsfound', 'block_lp_reportbuilder'),
        MSG_LOADING: M.util.get_string('loading', 'block_lp_reportbuilder'),
        generateRequest: generateRequest, 
        initialRequest: generateRequest(), // Initial request for first page of data 
        dynamicData: true, // Enables dynamic server-driven data 
        sortedBy : {key: defaultSortColumn, dir:YAHOO.widget.DataTable.CLASS_ASC}, // Sets UI initial sort arrow 
        paginator: new YAHOO.widget.Paginator({rowsPerPage: pageSize}), // Enables pagination  
        width: "100%"
    }; 

    var dataTable = new YAHOO.widget.ScrollingDataTable("recordstable", columnDefs, reportsDataSource, datatableConfigs);

    // Update totalRecords on the fly with values from server 
    dataTable.doBeforeLoadData = function(oRequest, oResponse, oPayload) { 
        if (!oResponse || !oResponse.meta) {
            return;
        }
        oPayload.totalRecords = oResponse.meta.totalRecords; 
        oPayload.pagination.recordOffset = oResponse.meta.startIndex; 
                
        var countText = '';
        
        if (oResponse.meta.totalRecords == 1) {
            countText = M.util.get_string('xrecord', 'block_lp_reportbuilder', oResponse.meta.totalRecords);
        }
        else {
            countText = M.util.get_string('xrecords', 'block_lp_reportbuilder', oResponse.meta.totalRecords);
        }

        var exportOptions = Y.one('#report-export-options');
        
        if (oResponse.meta.totalRecords == 0) {
            exportOptions && exportOptions.setStyle('display', 'none');
        }
        else {
            exportOptions && exportOptions.setStyle('display', 'inline');
        }
        
        Y.one('#totalCountLabel').set('text', countText);

        return oPayload; 
    };
    
     // loading panel for downloads
    Y.one('body').append(Y.Node.create([
        '<div id="loadingmsg" >',
            '<div class="yui3-widget-hd">',
                '<h4>' + M.util.get_string('generatingreport', 'block_lp_reportbuilder') + '<h4>',
            '</div>',
            '<div class="yui3-widget-bd">',
            '</div>',
        '</div>'].join('')
    ));
    var exportnode = Y.one('#report-export-options');
    exportnode && exportnode.on('click',function(e){
        var body = Y.one('body');
        var msg = Y.one('#loadingmsg');
        if (body && msg && loadingmsg) {
            var w = parseInt(msg.getStyle('width'),10);
            var h = parseInt(msg.getStyle('height'),10);
            var cw = e.target.get('winWidth');
            var ch = e.target.get('winHeight');
            msg.setStyle('left',cw*.5-w*.5);
            msg.setStyle('top',ch*.5-h*.5);
            loadingmsg.show();
        }
        return true;
    });
    
    var loadingmsg = new Y.Panel({
        srcNode: '#loadingmsg',
        width  : 220,
        zIndex : 99,
        modal  : true,
        visible: false,
        render : true,
        buttons : []
    });
    
    return { 
        ds: reportsDataSource, 
        dt: dataTable 
    }; 
}

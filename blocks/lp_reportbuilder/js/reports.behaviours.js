/**
 * @author rachael
 */
M.block_lp_reportbuilder = {};

M.block_lp_reportbuilder.init = function(Y) {	
    // declare all vars at top of function!
    var addcourse = false,
        removecourse = false,
        quickquiz = false,
        calendar = false,
        calendartarget = false,
        cleardate = false,
        panel = new Y.Panel({
            srcNode      : '#calendar',
            headerContent: 'Select a date',
            visible      : false,
            render       : true,
            plugins      : [Y.Plugin.Drag]
        });
    
    // toggle for add new new report button
    var report_index = Y.one('#page-admin-blocks-lp_reportbuilder-index');
    
    if (report_index && report_index.all('#mform1 legend:contains(New Report)').size() > 0) {

        //report_index.one('#mform1').addClass('hide');
	
        Y.one('button.add').on('click',function() {  
            Y.one('#page-admin-blocks-lp_reportbuilder-index #mform1').toggleClass('hide');
        });
    
    }
    
    // toggle function for search filters
    var report_search = Y.one('body');
     
    if (report_search.all('#mform1 #newfilterreports').size() > 0) {

        //report_search.one('#mform1 #newfilter').addClass('hide');
	
        Y.one('#show-hide-search-filter').on('click',function(e) {  
            Y.one('#mform1').toggleClass('hide');
        });
    
    }
        
    var add_rep_admin_btn = Y.one('#page-admin-blocks-lp_reportbuilder-reportadminsettings button.add-user');
    
    if (add_rep_admin_btn) {
        add_rep_admin_btn
            .on('click', function() { window.location = '/admin/roles/assign.php?contextid=1'; });
    }
    
    
    /** Hover **/
    Y.all('.no-overflow .reportbuilder-table td')
        .on(
            'hover',
            function(e) {
                e.target.ancestor('tr').addClass('hover');
                classes = e.target.getAttribute('class');
                // e.target.ancestor('table').one('col').addClass('hover');
            },
            function(e) {
                e.target.ancestor('tr').removeClass('hover');
                // e.target.ancestor('table').one('col').removeClass('hover');
            }
        );
    
    Y.all('.no-overflow table.reportbuilder-table').each(function(node) {
        
        node.insert('<div class="report-table"></div>','after');
    
        // Gathering data from table
        var cols = [];
        var data = [];
        var c = 0;
        
        node.all('th').each(function(node) {
            cols.push({key:'c'+c++, label:node.get('innerHTML')});
        });
        
        node.all('tr').each(function(node) {
            var c = 0;
            var row = {};
            node.all('td').each(function(node) {
                row['c'+c++] = node.get('innerHTML');
            });
            data.push(row);
        });
        
        // Construct Table with YUI widget
        report_table = new Y.DataTable.Base({
            columnset: cols,
            recordset: data
        });
        
        var vp_height = Y.one('body').get('winHeight');
        var new_height = (node.get('clientHeight') > vp_height) ? vp_height * 0.8 : 'auto';
        
        report_table.plug(Y.Plugin.DataTableScroll, {
            width: "100%",
            height: new_height
        });
        
        report_table.render(node.get('parentNode').one('.report-table'));
        
        // Hide original table
        node.setStyle('display', 'none');
    
    });
    
    // behaviors for course/quiz report
    // course select functionality
    addcourse = Y.one('input.addcourse');
    addcourse && addcourse.on('click', function(){ moveItems('course_from','course_to'); });
    removecourse = Y.one('input.removecourse');
    removecourse && removecourse.on('click', function(){ moveItems('course_to','course_from'); });
    function moveItems( fromel, toel ) {
        var from = Y.one('#'+fromel);
        var to = Y.one('#'+toel);
        var courses = [];
        var obj = false;
        var iter = 0;
        var max = 10;
        var list = false;
        
        // restrict size of course list to max
        if ('course_to' == toel){
            list = to.all('option');
            max -= list.size();
        }
                
        list = from.all('option');
        for (iter = 0; iter < list.size(); ++iter){
            obj = list.item(iter);
            if (obj && obj.get('selected')){
                if (max <= 0){
                    alert("You can only select 10 courses!");
                    break;
                }
                to.append(obj);
                --max;
            }
        }    
        
        // get included courses
        to = Y.one('#course_to');
        list = to.all('option');
        for (iter = 0; iter < list.size(); ++iter){
            obj = list.item(iter);
            courses.push(obj.get('value'));
        }
        
        to = Y.one('#courses');
        to.set('value',courses.join());
    }
    
    quickquiz = Y.one('#quick-quiz-course');
    quickquiz && quickquiz.delegate(
        'click',
        function(e){
            calendartarget = this;
            if (!calendar){
                calendar = new YAHOO.widget.Calendar('cal');
                calendar && calendar.render();
            }

            if (panel){
                panel.move(e.pageX+10,e.pageY-50);
                panel.show();
            }

            if (calendar){
                calendar.selectEvent.subscribe(updateDate);
            }
        },
        'input.date-picker'
    );
        
    cleardate = Y.one('#cleardate');
    cleardate && cleardate.on('click',function(){
        var inputs = Y.all('#quick-quiz-course input.date-picker');
        inputs && inputs.each(function(){
            this.set('value','');
        });
    });
        
    function updateDate(e, dates) {
        var d = dates[0][0];
        var input = calendartarget;
        input && input.set('value',d[2]+'-'+d[1]+'-'+d[0]);
        panel && panel.hide();
        calendar.selectEvent.unsubscribe(updateDate);
    }		
    
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

var exportnode = Y.one('#run_report');
    exportnode && exportnode.on('click',function(e){
        validateCourseQuiz(e);
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
    
    function validateCourseQuiz(e){
        e.preventDefault();
        var frm = document.forms['run_course_quiz'];
         if (!frm){
             alert("Error processing form!");
             return;
         }

         if ((frm.datefrom.value || frm.dateto.value) && !(frm.datefrom.value && frm.dateto.value)){
             alert("You need to select both a start and end date!");
             return;
         }

         if (!frm.courses.value){
             alert("You need to select at least one course to report on!");
             return;
         }

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

        Y.later(500, this, tryit, frm);
    }
    
    function tryit(frm) {
        frm && frm.submit();
    }
}



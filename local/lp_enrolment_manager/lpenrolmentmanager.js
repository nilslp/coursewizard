M.local_lp_enrolment_manager = {};

M.local_lp_enrolment_manager.init = function (Y) {
    var enrollist = {};
    var YAHOO = Y.YUI2;
    var hiertree = {};
    var summarypanel = new YAHOO.widget.Panel('lpsummarydiv', {
        width:'480px',
        visible : false, 
        draggable : true, 
        constraintoviewport : true,
        fixedcenter: 'contained',
        modal: false,
        iframe: true,
        zIndex : 1001
    }); 
    summarypanel && summarypanel.render();
    show('#lpsummarydiv');
    
    // Tabs
    Y.one('ul.tabs').delegate(
        'click',
        updateTabs,
        'li'
    );
        
    Y.one('ul.tabs').delegate(
        'hover',
        function(e) {
            e.target.addClass('hover');
        },
        function(e) {
            e.target.removeClass('hover');
        },
        'li'
    );
      
    
    // grab a YUI2 global and make use of treeview
    var tree = new YAHOO.widget.TreeView("lpcoursetree");
    tree && tree.render();
    show('#lpcoursetree');
    
    // handle leaf clicks - load users etc over ajax
    var list = Y.one('#lpcoursetree');
    list.delegate('click', function () {
        var id = this.get('id');
        var data = [];
        var el = {};
        
        el = Y.one('div.right-panel');
        el && el.removeClass('hide');
        
        el = Y.one('div.right-panel.init');
        el && el.hide();
        
        el = Y.all('#lpcoursetree div.ygtvitem');
        el && el.removeClass('selected');
        
        el = this.ancestor('div.ygtvitem');
        el.addClass('selected');
        
        id = id.substr(id.indexOf('_')+1);
        data['action'] = 'getcoursedesc';        
        data['id'] = id;
        data['clean'] = true;
        sendRequest(data);
    }, 'div.lpcoursetreeleaf > span');
    
    var form = Y.one('#enrolform');
    form && form.on('submit', function(e){
        e.preventDefault();
        submitForm();
    });
    
    form = Y.one('#searchfields');
    form && form.setStyle('visibility','hidden');
    
    Y.one('#detailpanel').delegate('click', function () {
        var p = this.get('id');
        p = p.substr(p.indexOf('_')+1);
        submitForm({'p':p});
    }, 'div.paging a' );
    
    Y.one('#clearsrch').on('click', function(e){
        Y.one('#srch').set('value','');
        submitForm();
    });
    
    Y.one('#showdesc').on('click', function(e){
        submitForm({'action':'getcoursedesc'});
    });
    
    Y.one('#showenrolled').on('click', function(e){
        submitForm({'action':'getenrolled'});
    });
    
    Y.one('#showadmins').on('click', function(e){
        submitForm({'action':'getadmins'});
    });
        
    Y.one('#enrolgroup').on('click', function(e){
        submitForm({'action':'gethierarchy'});
    });
    
    Y.one('#enrolindividual').on('click', function(e){
        submitForm({'action':'getindividuals'});
    });
    
    function updateTabs(e){
        var active = {};
        if (e){
            active = e.target;
        } else {
           active = Y.one('.tabs li'); // will return the first tab
        }
        var tabs = Y.all('.tabs li');
        tabs && tabs.removeClass('active');
        active && active.addClass('active');
    }
    
    function show( sel, bshow ) {
        var el = Y.one(sel);
        if (!el){
            return;
        } 
        (!bshow) ? el.removeClass('hide') : el.addClass('hide'); 
    }
    
    function updateEnrolment( page, id, state ) {
        if (!enrollist[page]){
             enrollist[page] = {};
        }
        enrollist[page][id] = state;
    }
    
    function checkEnrolment( page, id ) {
        return enrollist[page] && enrollist[page][id];
    }
    
    function sendRequest( data ){
        if (data.clean) { // clear users selected during individual enrolment
            enrollist = {};
        } 
        
        updateView(data);
                
        Y.io(M.cfg.wwwroot+'/local/lp_enrolment_manager/ajax.php', {
            method: 'POST',
            data: build_querystring(data),
            on: {
                complete: handleResponse         
            }
        });
    }
              
    function submitForm(extra) {
        var data = {action:'getenrolled'};
        /* tried a dozen ways to get ie6 to behave and give me my form elements,
         * the following odd piece of code is the only thing that worked ... DH
         */
        var elements = Y.all('.enrolform input');        
        elements.each(function(){
            if (Y.Array.indexOf(['hidden','text'],this.get('type')) !== -1) {
                data[this.get('id')] = this.get('value');
            }
        });
        
        if (extra) {
            data = Y.merge(data,extra);
        }
        
        sendRequest(data);
    }
    
    function getSummary ( data ) {        
        if (M.EMAILEDITOR) {
            data = Y.merge(data, M.EMAILEDITOR.getData());
        }
        data.action = 'enrolsummary';
        submitForm(data);
    }
    
    function enrolSummary( data ) {
        var summary = {};
        var preview = {};
        var body = {};
        var proceed = {};
        var proceedblock = {};
        var cancel = {};
        var labels = { preview:'Preview Email', template:'Show Template' };
               
        summary = Y.one('#lpsummary');
        summary.setContent('');
        
        summary.append( Y.Node.create( '<div class="label">Sender</div><div class="field">'+data.sender+'</div>' ) );
        summary.append( Y.Node.create( '<div class="label">Subject</div><div class="field">'+data.subject+'</div>' ) );
        summary.append( Y.Node.create( '<div class="label">Body</div>' ) );
        body = Y.Node.create('<div class="field">'+data.body+'</div>');
        preview = Y.Node.create( '<input type="button" value="'+labels.preview+'" />' );
        preview.on('click', function(){
            if (this.get('value') === labels.preview) {
                body.setContent(data.preview);
                this.set('value', labels.template);
            } else {
                body.setContent(data.body);
                this.set('value', labels.preview);                
            }
        });
         
        summary.append(preview);
        summary.append(body);
        
        summary.append( Y.Node.create('<div class="message">'+data.message+(data.warning ? '<div class="warning">'+data.warning+'</div>' : '')+'</div>') );
        
        cancel = Y.Node.create( '<input type="button" value="Cancel" />' );
        cancel.on('click', function(){
            summarypanel.hide();
        });
        
        proceed = Y.Node.create('<input type="button" value="Proceed" />');
        proceed.on('click', function(){
            submitForm({
                'action' : 'enrolusers',
                'body' : data.body,
                'sender' : data.sender,
                'subject' : data.subject,
                'users' : data.users,
                'sendemail' : 1
            });
        });
        
        proceedblock = Y.Node.create('<input type="button" value="Proceed without sending emails" />');
        proceedblock.on('click', function(){
            submitForm({
                'action' : 'enrolusers',
                'body' : data.body,
                'sender' : data.sender,
                'subject' : data.subject,
                'users' : data.users,
                'sendemail' : 0
            });
        });
        
        summary.append(cancel);
        summary.append(proceed);
        summary.append(proceedblock);
        
        summarypanel.show();
    }
    
    function updateView( data ) {  
        var loadingdivs = [];
        var i = 0;
        var keeploading = false;
        
        // show all initially hidden content
        show('div.right-panel.data');
        show('div#detailpanel');
        show('div#lpemaildiv');
                
        switch (data.action) {
            case 'getcoursedesc' :
            case 'getadmins' : 
            case 'gethierarchy' : 
            case 'getindividuals' : {
                data.success || loadingdivs.push(Y.one('#detailpanel .loading'));  
                break;          
            }
            case 'editemail' : {
                data.success || loadingdivs.push(Y.one('#lpemaildiv .loading'));
                M.EMAILEDITOR.show();
                break;
            }
            case 'enrolsummary': {
                data.success || loadingdivs.push(Y.one('#lpsummarydiv .loading'));
                summarypanel.show();
                break;
            }
            case 'enrolhierarchy' :
            case 'enrolusers' : {
                data.success || loadingdivs.push(Y.one('#lpsummarydiv .loading'));
                keeploading = true;
                break;
            }          
            default:                     
                break;
        }
        
        data.success && !keeploading && setTimeout(function(){ Y.all('.loading').addClass('hide') }, 500);
        
        // show loading graphic for all updating content
        for (i; i < loadingdivs.length; ++i){
            loadingdivs[i] && loadingdivs[i].removeClass('hide');
        }
    }
            
    function handleResponse(tid,outcome,args){
       try {
            var result = Y.JSON.parse(outcome.responseText);
            var action = '';
            if (result.success === 'true' || result.success === true) {
                action = result.action;
                switch (action) {
                    case 'getcoursedesc' :{
                        updateTabs();
                        buildCourseDescription(result);
                        buildFormFields({'id':result.course.id,'action':result.action}, true);
                        break;
                    }
                    case 'getenrolled' :
                    case 'getadmins' : {   
                        buildUserTable( result );
                        buildFormFields({'id':result.course.id,'action':result.action}, true);
                        break;          
                    }
                    case 'gethierarchy' : {
                        buildHierarchy(result);
                        break;
                    }  
                    case 'getindividuals' : {
                        buildUserTable( result );
                        buildFormFields({'id':result.course.id,'action':result.action}, true);                        
                        break;
                    } 
                    case 'editemail' : {
                        M.EMAILEDITOR.form(result);
                        break;
                    }
                    case 'enrolsummary': {
                        enrolSummary(result);
                        break;
                    }
                    case 'enrolhierarchy' :
                    case 'enrolusers' : {
                        result.redirect && (window.location = result.redirect);                       
                        break;
                    }          
                    default:                     
                        break;
                }                
            } else {
                handleAjaxFailure(result);
            } 
            updateView(result);
        } catch (e) {
            handleAjaxExeption(e);
        }
    }
    
    function handleAjaxFailure( result ) {
        if (result.message){
            alert(result.message);
        }
        if (result.redirect){
            window.location = result.redirect;
        }
    }
    
    function handleAjaxExeption( error ) {
        alert("There was a problem processing the response: " + error.message); 
    }
    
    function buildFormFields( fields, clean ) {
        if (!fields){
            return;
        }     
        
        var fieldset = Y.one('#hiddenfields');
        if (!fieldset){
            return;
        }
        
        if (clean){
            fieldset.setContent('');
        }
        
        for (var key in fields) {
            if (fields.hasOwnProperty(key)) {
                fieldset.append( Y.Node.create( '<input type="hidden" id="'+key+'" name="'+key+'" value="'+fields[key]+'" />' ) );
            }
        }
    }
    
    function buildCourseDescription( data ) {
        var coursedesc = data.course;
        var content = '';
        if (coursedesc){
            Y.one('#searchfields').setStyle('visibility','hidden');
            content = Y.Node.create('<div class="desc"></div>');
            content.append(Y.Node.create('<h2>'+data.heading+'</h2>'));
            content.append( Y.Node.create('<dl class="title"><dt>NAME:</dt><dd>'+coursedesc.fullname+'</dd></dl>') );
            content.append( Y.Node.create('<dl><dt>CATEGORY:</dt><dd>'+coursedesc.category+'</dd></dl>') );
            content.append( Y.Node.create('<dl><dt>START:</dt><dd>'+coursedesc.startdate+'</dd></dl>') );
            content.append( Y.Node.create('<dl><dt>CREATED:</dt><dd>'+coursedesc.timecreated+'</dd></dl>') );
            content.append( Y.Node.create('<dl><dt>SUMMARY:</dt><dd>'+coursedesc.summary+'</dd></dl>') );
            Y.one('#detailpanel').setContent( content );
        }
    }
    
    function buildPagingList( currentpage, numpages, perpage, classes ) { 
        var pagingtemplate = '<a href="javascript: void(0);" id="pag_%index%" class="">&nbsp;%index%&nbsp;</a>';       
        var paginglist = [];
        var pagestart = 1;
        var pageend = 1;
        var pageindex = 1;
        var maxpages = 5;
        var prepend = '';
        var append = '';
        var intervaltext = '<a href="javascript: void(0);" class="disabled">...</a>';
        
        paginglist.push('<div class="'+classes.paging+'">');
        perpage = perpage || 1;
        pagestart = Math.max(1,currentpage-maxpages);
        pageend = Math.min(currentpage+maxpages, numpages);
        for (i = pagestart; i <= pageend; ++i) {
            pageindex = i;
            prepend = append = '';
            if (numpages > pageend) {
                if (pageindex === pageend) {                    
                    pageindex = numpages;
                    prepend = intervaltext;
                }
            } 
            
            if (pagestart > 1) {
                if (pageindex === pagestart) {
                    pageindex = 1;
                    append = intervaltext;
                }
            }
            prepend && paginglist.push(prepend);
            paginglist.push( (currentpage == pageindex) ? pagingtemplate.replace(/%index%/g, pageindex).replace(/class=\"\"/,'class="disabled"') : pagingtemplate.replace(/%index%/g, pageindex) );
            append && paginglist.push(append);
        }
        paginglist.push('</div>');
        return paginglist;
    }
    
    function buildUserTable( data ){   
        var userdiv = {};
        var content = {};
        var panel = {};
        var tbody = {};  
        var enrol = {};   
        var edit = {};
        var paginglist = [];
        var i = 0;
        var uid = '';
        var uinitial = '0';
        var checkcount = 0;
        var table = {};
        var checkall = {}; 
        var checks = {};
        var results = { 
            'buttontext':'Enrol',
            'users':[],
            'heading':'Users',
            'depttitle':'Department',
            'nametitle':'Name',
            'totalusers':'Unknown',
            'totaldesc':'Total users enrolled',
            'tableid':'usertable',
            'panel':'#detailpanel',
            'page': 1,
            'perpage': 30,
            'checkable':false,
            'classes':Y.merge(
                {
                    'table':'generaltable',
                    'th':'header',
                    'tr':'r',
                    'td':'cell c',
                    'paging':'paging'
                },
                data.classes
            )
            }; 
        results = Y.merge(results,data);
        
        // a little housekeeping
        checkall = Y.one('#chkallusr');
        checkall && checkall.detach('click');
        
        table = Y.one('#'+results.tableid);
        table && table.detach('click');
                 
        if (results.users) {
            Y.one('#searchfields').setStyle('visibility','visible');
        } 
            
        if (results.users) {   
            // build page navigation
            if (results.totalusers > results.perpage) {
               paginglist = buildPagingList(results.page, Math.ceil(results.totalusers/results.perpage), results.perpage, results.classes);
            }
            
            content = Y.Node.create('<table class="'+results.classes.table+'" id="'+results.tableid+'"></table>');
            tbody = Y.Node.create('<tbody></tbody>');
            
            for (i = 0; i < results.users.length; ++i){
                uinitial = results.users[i].fullname.substr(0,1);
                uid = uinitial + '_'+results.users[i].id;
                tbody.append( Y.Node.create('<tr class="'+results.classes.tr+(i%2)+' item '+((i%2)?'odd':'even') + '">' +
                    (results.checkable ? '<td class="'+results.classes.td+'0"><input class="chkusr" type="checkbox" id="'+uid+'" name="'+uid+'"'+
                        (checkEnrolment(uinitial, results.users[i].id) ? ' checked="'+(++checkcount)+'" ' : '')+ ' /></td>' : '') + 
                    '<td class="'+results.classes.td+'1"><span><a href="/user/view.php?id='+results.users[i].id+'">' + results.users[i].fullname+'</a></span></td>' +
                    '<td class="'+results.classes.td+'2"><span class="meta" title="'+results.users[i].hiername+'">'+results.users[i].hiername+'</span></td>' +
                    '<td class="'+results.classes.td+'3"><span class="meta" title="Account Status">'+results.users[i].accountstatus+'</span></td>' + 
                    '</tr>') );
            }     
            
            content.append( Y.Node.create('<thead><tr><td colspan="4">'+paginglist.join('')+'</td></tr><tr>'+
                (results.checkable ? '<th class="'+results.classes.th+'"><input type="checkbox" id="chkallusr" name="chkallusr" '+((results.users.length === checkcount)?'checked="checked"':'')+'/></th>' : '')+
                '<th class="'+results.classes.th+'">'+results.nametitle+'</th><th class="'+results.classes.th+'"><span>'+results.depttitle+'</span></th><th class="'+results.classes.th+'"><span>Account Status</span></th></tr></thead>') );
                      
            content.append( tbody );
            
            panel = Y.one(results.panel);
            if (panel){
                panel.setContent('');
                panel.append(Y.Node.create('<h2>'+results.heading+'</h2>'));
                panel.append( 
                    Y.Node.create('<span class="metatitle">'+results.totaldesc+': </span><span class="metavalue">'+results.totalusers+'</span>') 
                );
                panel.append( content );
            }
            
            
            if (results.checkable) {
                content.delegate('click', function (e){ // 'content' is our table
                    var checked = this.get('checked');
                    var id = this.get('id');
                    updateEnrolment(id.substr(0,1), id.substr(id.lastIndexOf('_')+1), checked);     
                    !checked && Y.one('#chkallusr').set('checked',false);               
                }, 'input.chkusr');
                
                checkall = Y.one('#chkallusr');
                checkall && checkall.on('click', function(e){
                    checks = checkall.ancestor('table').all('input.chkusr');
                    checks && checks.each(function(){                    
                        var id = this.get('id');
                        var checked = checkall.get('checked');
                        this.set('checked', checked);
                        updateEnrolment(id.substr(0,1), id.substr(id.lastIndexOf('_')+1), checked);  
                    });                    
                });
                
                enrol = Y.Node.create( '<input id="enrolbyid" name="enrolbyid" type="button" value="'+results.buttontext+'" />' ); 
                enrol.on('click', function(e){
                    // build enrolment string
                    var userids = [];
                    var k = '';
                    var j = '';
                    for (k in enrollist) {
                        if (enrollist.hasOwnProperty(k)){
                            for (j in enrollist[k]) {
                                if (enrollist[k].hasOwnProperty(j)) {
                                    enrollist[k][j] && userids.push(j); 
                                }
                            }
                        }
                    }
                    
                    if (userids.length === 0){
                        alert('You must select one or more users to enrol!');
                        return;
                    }
                    getSummary({'userids':userids.join('-')});
                });
                content.insert(enrol, 'after');
                
                content.insert(buildEmailButton(), 'after');                
            }
        } 
    }
    
    function buildHierarchy( data ) {
        var list = '';
        var content;
        var atts = '';
        var panel = {};
        var i = 0;
        var enrol = {};
        var edit = {};
        var results = { 
            'hierarchy':[],
            'metatarget':'lpuserstats',
            'panel':'detailpanel',
            'buttontext':'Enrol',
            'heading':'Users'
            }; 
        results = Y.merge(results,data);
        
        if (!results.hierarchy){
            return;
        }
        
        Y.one('#searchfields').setStyle('visibility','hidden');
        
        hiertree.unsubscribe && (hiertree.unsubscribe('clickEvent'));
        
        panel = Y.one('#'+results.panel);
        if (!panel){
            throw new Error("Could not find the element specified in the response: " + results.panel);
            return;
        }
        panel.setContent( '' );
        panel.append(Y.Node.create('<h2>'+results.heading+'</h2>'));
        panel.append( Y.Node.create('<div id="lpuserlist" class="whitebg ygtv-checkbox"></div>') );
        
        hiertree = new YAHOO.widget.TreeView('lpuserlist');
        renderNodes(results.hierarchy.children, {}, hiertree.getRoot());
        hiertree.setNodesProperty('propagateHighlightUp',true);
        hiertree.setNodesProperty('propagateHighlightDown',true);
        hiertree.subscribe('clickEvent',hiertree.onEventToggleHighlight);  
        hiertree.render(); 
              
        enrol = Y.Node.create( '<input id="enrolhier" name="enrolhier" type="button" value="'+results.buttontext+'" />' ); 
        enrol.on('click', function(e){    
            var hierids = [];  
            var hid = '';   
            var i = 0;   
            var hiLit = hiertree.getNodesByProperty('highlightState',1);
            if (hiLit) {
                var labels = [];
                for (i = 0; i < hiLit.length; i++) {
                    hid =  hiLit[i].data.id;
                    hierids.push(hid.substr(hid.lastIndexOf('_') + 1));
                }
            }
            
            if (hierids.length === 0) {
                alert('You must select one or more group to enrol!');
                return;                
            }
            
            getSummary({'hierids':hierids.join('-')});
        });
       
        list = Y.one('#lpuserlist');
        list.append( buildEmailButton() );
        list.append( enrol );        
    }
    
    function buildEmailButton() {
        var btn = {},
            atts = '';
        enrol_cfg.email_enabled || (atts = 'disabled="disabled" title="Emails will not be sent - Bulk email enrolment is turned off - please contact support."'); 
        btn = Y.Node.create( '<input type="button" value="Edit Email" '+atts+'/>' ); 
        btn.on('click', function(e){ 
            submitForm({'action':'editemail'});
        });
        return btn;
    }
    
    function renderNodes( nodes, atts, parent ) {
        var k = '';
        var node = {};
        var idprefix = parent.data.id ? parent.data.id + '_' : 'hier_';
        for (k in nodes) {
            if (!nodes.hasOwnProperty(k)){
                continue;
            }
            
            node = new YAHOO.widget.TextNode({label:nodes[k].name,id:idprefix+k}, parent, true);
            node.collapse();
            renderNodes(nodes[k].children,{},node);
        }
    }
}
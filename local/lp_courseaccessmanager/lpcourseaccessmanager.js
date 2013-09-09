M.local_lp_courseaccessmanager = {};

M.local_lp_courseaccessmanager.init = function (Y) {
    
    var addrule = {};
    var YAHOO = Y.YUI2;
    
    addrule = Y.one('#btn_newrule');
    addrule && addrule.on('click',function(){
       submitForm({'action':'createrule'});
    });    
    
    // on load, get current rules
    submitForm({'action':'getrules'});
    
    function controls(){
    // grab a YUI2 global and make use of treeview
    /*var tree = new YAHOO.widget.TreeView("lpcoursetree");
    tree && tree.render();
    show('#lpcoursetree');
    Y.one('#lpcourselist .loading').addClass('hide');
    
    // handle leaf clicks - load users etc over ajax
    var list = Y.one('#lpcoursetree');
    list.delegate('click', function () {
        var id = this.get('id');
        id = parseInt(id.substr(id.lastIndexOf('_')+1));
        var data = [];
        data['action'] = 'getaccess';        
        data['course'] = id;
        data['clean'] = true;
        sendRequest(data);
    }, 'div.lpcoursetreeleaf > span, table.lpcoursetreebranch');*/
    
    /*var srchform = Y.one('#grantform');
    srchform.on('submit', function(e){
        e.preventDefault();
        submitForm();
    });
    srchform.setStyle('visibility','hidden');*/
    
    /*Y.one('#lpuserlist').delegate('click', function () {
        var p = this.get('id');
        p = p.substr(p.indexOf('_')+1);
        submitForm({'p':p});
    }, 'div.paging a' );*/
    }
    
    /*Y.one('#clearsrch').on('click', function(e){
        Y.one('#srch').set('value','');
        submitForm();
    });
        
    Y.one('#grantgroup').on('click', function(e){
        submitForm({'action':'gethierarchy'});
    });
    
    Y.one('#grantindividual').on('click', function(e){
        submitForm({'action':'getindividuals'});
    });*/
    
    function toggleRule(ruleid, active){
        var el = Y.one('#toggle_rule_'+ruleid+' a');        
        var li = {};
        if (el){
            li = el.ancestor('span.title');
            if (active){
                el.replaceClass('inactive','active');
                li && li.removeClass('deactivated');
                el.setAttribute('title','Enabled - Rule is active and in effect.')
            } else {
                el.replaceClass('active','inactive');
                li && li.addClass('deactivated');
                el.setAttribute('title','Disabled - Rule has been disabled.')
            }
        }
    }
    
    function toggleDeepLinking(ruleid, allowdeeplinking){
        var el = Y.one('#deeplink_rule_'+ruleid+' a');        
        if (el){
            if (allowdeeplinking){
                el.replaceClass('deny-link','allow-link');
                el.setAttribute('title','Allowed - Courses can still be accessed with direct link.')
            } else {
                el.replaceClass('allow-link','deny-link');
                el.setAttribute('title','Denied - Courses cannot be accessed with direct link.')
            }
        }
    }
    
    function clearPanels(){
        var containers = ['rulelist','coursetree','hiertree','userlist'];
        var panels = ['students','course-list'];
        var container = {};
        var iter = 0;
        for (iter = 0; iter < containers.length; ++iter){            
            container = Y.one('#'+containers[iter]);
            container && container.setContent('');
        }
        for (iter = 0; iter < panels.length; ++iter){            
            container = Y.one('div.'+panels[iter]);
            container && container.addClass('hide');
        }
    }
        
    function show( sel, bshow ) {
        var el = Y.one(sel);
        if (!el){
            return;
        } 
        (!bshow) ? el.removeClass('hide') : el.addClass('hide'); 
    }
    
    function updategrants( page, id, state ) {
        if (!grantlist[page]){
             grantlist[page] = {};
        }
        grantlist[page][id] = state;
    }
    
    function checkgrants( page, id ) {
        return grantlist[page] && grantlist[page][id];
    }
    
    function sendRequest( data ){
        if (data.clean) { // clear users selected during individual grantment
            grantlist = {};
        } 
        
        updateView(data);
                
        Y.io(M.cfg.wwwroot+'/local/lp_courseaccessmanager/ajax.php', {
            method: 'POST',
            data: build_querystring(data),
            on: {
                complete: handleResponse         
            }
        });
    }
              
    function submitForm(extra) {
        var data = {action:'getaccess'};
        /* tried a dozen ways to get ie6 to behave and give me my form elements,
         * the following odd piece of code is the only thing that worked ... DH
         */
        var elements = Y.all('#accessform input');        
        elements.each(function(node){
            if (Y.Array.indexOf(['hidden','text'],node.get('type')) !== -1) {
                data[node.get('id')] = node.get('value');                
            }
        });
        
        if (extra) {
            data = Y.merge(data,extra);
        }
        
        sendRequest(data);
    }
    
    function summaryview(){
    /*function getSummary ( data ) {    
        data.action = 'grantsummary';
        submitForm(data);
    }
    
    function grantSummary( data ) {
        var summary = {};
        var proceed = {};
        var cancel = {};
               
        summary = Y.one('#lpsummary');
        summary.setContent('');
                
        summary.append( Y.Node.create('<div class="message">'+data.message+(data.warning ? '<div class="warning">'+data.warning+'</div>' : '')+'</div>') );
        
        cancel = Y.Node.create( '<input type="button" value="Cancel" />' );
        cancel.on('click', function(){
            summarypanel.hide();
        });
        
        proceed = Y.Node.create('<input type="button" value="Proceed" />');
        proceed.on('click', function(){
            submitForm({
                'action' : 'grantusers',
                'users' : data.users
            });
        });
        
        summary.append(cancel);
        summary.append(proceed);
        
        summarypanel.show();
    }*/
     
    }
    
    function updateView( data ) {
    }
            
    function handleResponse(tid,outcome,args){
       try {
            var result = Y.JSON.parse(outcome.responseText);
            var action = '';
            if (result.success === 'true' || result.success === true) {
                addrule && addrule.set('disabled',false);
                action = result.action;
                switch (action) {
                    case 'getrules' : {
                        clearPanels();
                        buildRuleList( result.rules );
                        break;          
                    }
                    case 'createrule' : {
                        addrule && addrule.set('disabled','disabled');
                        appendRule(result.rule);
                        buildCourseTree(result.courses);
                        buildAssignments(result.hierarchy, result.users);
                        break;          
                    }
                    case 'viewrule' : {
                        buildCourseTree( result.courses, true);
                        buildAssignments( result.hierarchy, result.users, true);
                        break;          
                    }
                    case 'togglerule': {
                        toggleRule(result.rule,result.active);
                        break;
                    }  
                    case 'deeplinkrule': {
                        toggleDeepLinking(result.rule,result.allowdeeplinking);
                        break;
                    } 
                    case 'deleterule': {
                        clearPanels();
                        break;
                    } 
                    /*case 'getindividuals' : {
                        buildUserTable( result );
                        buildFormFields({'course':result.course.id,'action':result.action}, true);                        
                        break;
                    } 
                    case 'grantsummary': {
                        grantSummary(result);
                        break;
                    }
                    case 'granthierarchy' :
                    case 'grantusers' : {
                        result.redirect && (window.location = result.redirect);                       
                        break;
                    }          */
                    default:
                        break;
                }           
                
                // perform chained actions
                if (result.chainaction){
                    result.action = result.chainaction;
                    submitForm(result);
                }
                
            } else if (result.confirm) {
                if (confirm(result.confirm)){
                    result.confirmed = 1;
                    submitForm(result);
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
        var extra = String(error).indexOf('JSON.parse') ? "\n(Please ensure debug display is not enabled)" : "";
        alert("There was a problem processing the response: " + error.message + extra); 
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
        
    function buildPagingList( currentpage, numpages, perpage, classes ) { 
        var pagingtemplate = '<a href="javascript: void(0);" id="pag_%index%">&nbsp;%index%&nbsp;</a>';       
        var paginglist = [];
        var pagestart = 1;
        var pageend = 1;
        var pageindex = 1;
        var maxpages = 5;
        var prepend = '';
        var append = '';
        var intervaltext = '&nbsp;...&nbsp;';
        
        paginglist.push('<div class="'+classes.paging+'">');
        paginglist.push('Page:&nbsp;&nbsp;');
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
            paginglist.push( (currentpage == pageindex) ? '&nbsp;'+pageindex+'&nbsp;' : pagingtemplate.replace(/%index%/g, pageindex) );
            append && paginglist.push(append);
        }
        paginglist.push('</div>');
        return paginglist;
    }
    
    function buildCourseTree( courses, readonly ) {
        var container = Y.one('div.course-list');
        var input = Y.one('#courseids');
        var tree = {};
        input && input.set('value','');
        if (container){
            container.removeClass('hide');
            readonly ? container.removeClass('ygtv-checkbox') : container.addClass('ygtv-checkbox');
        }
        
        if (courses){
            tree = new YAHOO.widget.TreeView('coursetree');
            renderNodes(courses.nodes.children, {}, tree.getRoot(),'context_');
            tree.setNodesProperty('propagateHighlightUp',true);
            tree.setNodesProperty('propagateHighlightDown',true);
            if (!readonly){
                tree.subscribe('clickEvent',onEventToggleHighlight);  
            }
            tree.render(); 
        }
        
    }
    
    function buildAssignments( hierarchy, userlist, readonly ) {        
        var container = Y.one('div.students');
        var input = Y.one('#hierids');
        var tree = {};
        input && input.set('value','');
        if (container){
            container.removeClass('hide');
            readonly ? container.removeClass('ygtv-checkbox') : container.addClass('ygtv-checkbox');
        }
        
        if (hierarchy){
            tree = new YAHOO.widget.TreeView('hiertree');
            renderNodes(hierarchy.nodes.children, {}, tree.getRoot(),'hier_');
            tree.setNodesProperty('propagateHighlightUp',true);
            tree.setNodesProperty('propagateHighlightDown',true);
            if (!readonly){
                tree.subscribe('clickEvent',onEventToggleHighlight);  
            }
            tree.render();
        }
    }
    
    function appendRule(rule){
        var ul = Y.one('#rulelist ul');
        var input = Y.one('#rule_name');
        var selected = {};
        if (!ul){
            return;
        }
        input && input.set('value',rule.name);
        selected = ul.all('.selected');
        selected && selected.removeClass('selected');
        ul.append( Y.Node.create(createRuleElement(rule)) );
    }
    
    function buildRuleList( rules ){  
        var rulescontainer = {};
        var ul = [];
        var iter = 0;
        if (!rules){
            return;
        }
        
        // get and validate our container element
        rulescontainer = Y.one('#rulelist');
        if (!rulescontainer){
            return;
        }
        
        rulescontainer.detach('click');
        rulescontainer.detach('change');
        rulescontainer.setContent('');
        ul.push('<ul>');
        if (rules.length){
            for (iter = 0; iter < rules.length; ++iter){
                ul.push( createRuleElement(rules[iter]) );
            }
        } else {            
            ul.push(
                '<li class="no-rules"><span class="title">No access rules defined</span></li>'
            );
        }
        ul.push('</ul>');
        
        rulescontainer.append(Y.Node.create(ul.join('')));
        rulescontainer.all('.selected').removeClass('selected');
        rulescontainer.delegate(
            'click', 
            function (e){ 
                e.stopPropagation();
                var id = e.currentTarget.get('id');
                var action = {};
                var ruleid = {};
                var rulename = '';
                var li = {};
                if (!id || id.indexOf('yui')!==-1){
                    return;
                }
                                
                rulename = rulescontainer.one('input.rname');
                rulename && (rulename = rulename.get('value'));
                action = id.substr(0,id.indexOf('_'))+'rule';
                ruleid = id.substr(id.lastIndexOf('_')+1);
                submitForm({'action':action, 'rule':ruleid, 'rule_name':rulename});
                rulescontainer.all('.selected').removeClass('selected');
                
                if (action !== 'saverule'){
                    li = rulescontainer.one('ul li.newrule');
                    li && li.remove(true);
                }
                
                li = e.currentTarget.ancestor('li.item-rule');
                li && li.addClass('selected');
            },
            'li.item-rule span.title, ul.controls li.save, ul.controls li.toggle, ul.controls a.delete'
        );
            
        addrule && addrule.removeClass('hide');
    }
    
    function updateName(val){
        alert(val);
    }
    
    function createRuleElement( rule ){
        var ruleid = rule.id ? 'rule_'+rule.id : 'newrule';
        var li = [];
        var controls = [];
        
        li.push('<li class="item-rule'+(rule.id?'':' newrule selected')+'">');
        if (rule.id){
            li.push('<span title="Click to view rule" id="view_'+ruleid+'" class="title'+(rule.active==='1'?'':' deactivated')+'">');
            li.push(rule.name);
        } else {
            li.push('<span class="title">');
            li.push('<input class="rname" type="text" value="'+rule.name+'" />');
            // onchange="var rn = document.getElementById(\'rule_name\'); rn && rn.setAttribute(\'value\',this.getAttribute(\'value\'));" 
        }
        
        controls.push('<ul class="controls">');
        if (rule.id) {
            var active_title = (rule.active==='1') ? 'Enabled - Rule is active and in effect.' : 'Disabled - Rule has been disabled.';
            var link_title = (rule.allowdeeplinking==='1') ? 'Allowed - Courses can still be accessed with direct link.' : 'Denied - Courses cannot be accessed with direct link.';
            controls.push('<li class="toggle" id="toggle_'+ruleid+'"><a class="'+(rule.active==='1'?'':'in')+'active" title="'+active_title+'"></a></li>');
            controls.push('<li class="toggle" id="deeplink_'+ruleid+'"><a class="'+(rule.allowdeeplinking==='1'?'allow':'deny')+'-link" title="'+link_title+'"></a></li>');
            controls.push('<li><a class="delete" id="delete_'+ruleid+'" title="Delete this rule"></a></li>');
        } else {
            controls.push('<li class="save" id="save_'+ruleid+'"><button>Save</button></li>');
        }
        controls.push('</ul>');
        
        li.push(controls.join(''));
        li.push('</span>');
        li.push('</li>');
        return li.join('');
    }
    
    function tables(){
    /*function buildUserTable( data ){   
        var userdiv = {};
        var content = {};
        var tbody = {};  
        var grant = {};   
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
            'buttontext':'Grant Access',
            'users':[],
            'depttitle':'Department',
            'nametitle':'Name',
            'totalusers':'Unknown',
            'tableid':'usertable',
            'listtarget':'#lpuserlist',
            'metatarget':'#lpuserstats',
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
            Y.one('#grantform').setStyle('visibility','visible');
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
                        (checkgrantment(uinitial, results.users[i].id) ? ' checked="'+(++checkcount)+'" ' : '')+ ' /></td>' : '') + 
                    '<td class="'+results.classes.td+'1"><span><a href="javascript: void(0);">' + results.users[i].fullname+'</a></span></td>' +
                    '<td class="'+results.classes.td+'2"><span class="meta" title="'+results.users[i].hiername+'">'+results.users[i].hiername+'</span></td>' +
                    '<td class="'+results.classes.td+'3"><span class="meta" title="Account Status">'+results.users[i].accountstatus+'</span></td>' + 
                    '</tr>') );
            }     
            
            content.append( Y.Node.create('<thead><tr><td colspan="4">'+paginglist.join('')+'</td></tr><tr>'+
                (results.checkable ? '<th class="'+results.classes.th+'"><input type="checkbox" id="chkallusr" name="chkallusr" '+((results.users.length === checkcount)?'checked="checked"':'')+'/></th>' : '')+
                '<th class="'+results.classes.th+'">'+results.nametitle+'</th><th class="'+results.classes.th+'"><span>'+results.depttitle+'</span></th><th class="'+results.classes.th+'"><span>Account Status</span></th></tr></thead>') );
                      
            content.append( tbody );
            
            userdiv = Y.one(results.listtarget);
            userdiv && userdiv.setContent( content );
            Y.one(results.metatarget).setContent( 
                Y.Node.create('<span class="metatitle">'+results.totaldesc+': </span><span class="metavalue">'+results.totalusers+'</span>') 
            );
            
            
            if (results.checkable) {
                content.delegate('click', function (e){ // 'content' is our table
                    var checked = this.get('checked');
                    var id = this.get('id');
                    updategrants(id.substr(0,1), id.substr(id.lastIndexOf('_')+1), checked);     
                    !checked && Y.one('#chkallusr').set('checked',false);               
                }, 'input.chkusr');
                
                checkall = Y.one('#chkallusr');
                checkall && checkall.on('click', function(e){
                    checks = checkall.ancestor('table').all('input.chkusr');
                    checks && checks.each(function(){                    
                        var id = this.get('id');
                        var checked = checkall.get('checked');
                        this.set('checked', checked);
                        updategrants(id.substr(0,1), id.substr(id.lastIndexOf('_')+1), checked);  
                    });                    
                });
                
                grant = Y.Node.create( '<input id="grantbyid" name="grantbyid" type="button" value="'+results.buttontext+'" />' ); 
                grant.on('click', function(e){
                    // build grant string
                    var userids = [];
                    var k = '';
                    var j = '';
                    for (k in grantlist) {
                        if (grantlist.hasOwnProperty(k)){
                            for (j in grantlist[k]) {
                                if (grantlist[k].hasOwnProperty(j)) {
                                    grantlist[k][j] && userids.push(j); 
                                }
                            }
                        }
                    }
                    
                    if (userids.length === 0){
                        alert('You must select one or more users to grant access!');
                        return;
                    }
                    getSummary({'userids':userids.join('-')});
                });
                content.insert(grant, 'after');
                
                content.insert(buildEmailButton(), 'after');                
            }
        } 
    }*/
    
    /*function buildHierarchy( data ) {
        var list = '';
        var content;
        var atts = '';
        var i = 0;
        var grant = {};
        var edit = {};
        var results = { 
            'hierarchy':[],
            'listtarget':'lpuserlist',
            'metatarget':'lpuserstats',
            'buttontext':'Grant Access'
            }; 
        results = Y.merge(results,data);
        
        if (!results.hierarchy){
            return;
        }
        
        hiertree.unsubscribe && (hiertree.unsubscribe('clickEvent'));
        
        list = Y.one('#'+results.listtarget);
        list.setContent( '' );
        
        hiertree = new YAHOO.widget.TreeView('lpuserlist');
        renderNodes(results.hierarchy.children, {}, hiertree.getRoot(),'hier_');
        hiertree.setNodesProperty('propagateHighlightUp',true);
        hiertree.setNodesProperty('propagateHighlightDown',true);
        hiertree.subscribe('clickEvent',hiertree.onEventToggleHighlight);  
        hiertree.render(); 
              
        grant = Y.Node.create( '<input id="granthier" name="granthier" type="button" value="'+results.buttontext+'" />' ); 
        grant.on('click', function(e){    
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
                alert('You must select one or more group to grant!');
                return;                
            }
            
            getSummary({'hierids':hierids.join('-')});
        });
       
        list = Y.one('#'+results.listtarget);
        list.append( grant );        
    }*/
    }

    function renderNodes( nodes, atts, parent, defaultprefix ) {
        var k = '';
        var node = {};
        var nodedata = {};
        defaultprefix = defaultprefix || 'node_';
        var idprefix = parent.data.id ? parent.data.id + '_' : defaultprefix;
        for (k in nodes) {
            if (!nodes.hasOwnProperty(k)){
                continue;
            }
            
            nodedata = {
                label:nodes[k].name,
                id:idprefix+k,
                category:nodes[k].category,
                labelStyle:'ygtvlabel'
            };
            
            if (nodes[k].data && nodes[k].data.css_class){
                nodedata.labelStyle = nodes[k].data.css_class;                
            } 
                            
            node = new YAHOO.widget.TextNode(
                nodedata, 
                parent, 
                true);              
            
            node.collapse();
            renderNodes(nodes[k].children,{},node);
        }
    }
    
    function onEventToggleHighlight( args ) {
        var id = this.getEl().id;
        var trees = {'coursetree':'courseids','hiertree':'hierids'};
        var input = Y.one('#'+trees[id]);
        var ret = this.onEventToggleHighlight( args );
        var idlist = [];  
        var hid = '';   
        var i = 0;   
        var hiLit = {};
        var prefix = (id == 'coursetree') || '';
        if (!input){
            return ret;
        }
        hiLit = this.getNodesByProperty('highlightState',1);
        if (hiLit) {
            for (i = 0; i < hiLit.length; i++) {
                hid =  hiLit[i].data.id;
                prefix && (prefix = hiLit[i].data.category ? 'course_' : 'category_');
                idlist.push(prefix+hid.substr(hid.indexOf('_') + 1));
            }
        }
        input.set('value',idlist.join());
        return ret;
    }
    
}

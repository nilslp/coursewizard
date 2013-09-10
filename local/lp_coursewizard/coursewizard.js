M.local_lp_coursewizard = {};

M.local_lp_coursewizard.init = function(Y,sesskey,siteurl,courseId) {

    //global variables
    var currentResource;        //the currently selected resource
    var ajaxloading = false;    //if an ajax request is currently running
    var currentTab = 'tab2';    //set default tab as tab2
    
    //page elements
    var wrapper = Y.one('#wizard-wrapper');
    var togglebutton = Y.one('#wizard-toggle-button');
    var btncreatecourse = Y.one('#create-course');
    var sectionselect = Y.one('#sectionSelect');
    var resourceuploadinput = Y.one('input#upload-resource');
    var scormuploadinput = Y.one('input#upload-scorm');
    var resourcetable = Y.one('table#currentresourcestable');
    var updateModuleButtons = Y.all('input.btn_update_resource');
    var scormCompletionSelect = Y.one('#scorm-completion-container');
    var resourceCompletionSelect = Y.one('#resource-completion-container');
    var tabaddscorm = Y.one('#theTabScorm');
    var tabaddfile = Y.one('#theTabFile');
    var btnsavecompletion = Y.one('#save-completion');
    var enrolledusertable = Y.one('#enrolledusertable');
    var unenrolledusertable = Y.one('#unenrolledusertable');
    var btnenrolusers = Y.one('#btn-enrol-users');
    var btnpublishcourse = Y.one('#publish-button');
    var tabControls = Y.all('div#tabs-container ul.tabs li');
    var minimiseBtn = Y.one('#tabs-visibility-controls li#hide');
    var closeBtn = Y.one('#tabs-visibility-controls li#close');

////////////////////////////////////////////////////////////////////////////////
/////////////////////////AJAX Functions ////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    
    function ajaxRequest(url,method,paramstring,callback){
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange=function(){
            if (xhr.readyState==4 && xhr.status==200){
                var data = Y.JSON.parse(xhr.responseText);
                if(callback !== null){
                    callback(data);
                }
            }
        };
        xhr.open(method, url, true);
        xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhr.send(paramstring);
    }
    function ioFileUpload(url,formObj,callback){
        // Create a YUI instance using the io-upload-iframe sub-module.
        // This is needed so ie8 can also upload via javascript
        YUI().use("io-upload-iframe", function(Y) {
            var cfg = {
                method: 'POST',
                form: {
                    id: formObj,
                    upload: true
                }
            };
            function complete(id, o, args) {
                var data = JSON.parse(o.responseText);
                callback(data);
            };
            Y.on('io:complete', complete, Y);

            // Start the transaction.
            var request = Y.io(url, cfg);
        });
    }
    
    function ajaxBegin(type){
        ajaxloading = true;
        showHide('span.ajax_message_'+type, '.ajax_starter_'+type, true);
    }
    
    function ajaxEnd(type){
        ajaxloading = false;
        showHide('.ajax_starter_'+type, 'span.ajax_message_'+type, true);
    }
    
////////////////////////////////////////////////////////////////////////////////
/////////////////////END AJAX Functions ////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    

    
////////////////////////////////////////////////////////////////////////////////
///////////////////// Business Functions ///////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    /*
     * function to toggle visibility of groups or pairs of elements
     */
    function showHide(showelement,hideelement,all){
        if(all){
            Y.all(showelement).removeClass('hide').addClass('show');
            Y.all(hideelement).removeClass('show').addClass('hide');
        }
        else{
            Y.one(showelement).removeClass('hide').addClass('show');
            Y.one(hideelement).removeClass('show').addClass('hide');
        }
    }
    /*
     * function to get the selected value of a YUI select object
     */
    function getSelectValue(select){
        var value = 0;
        if(select){
            select.get("options").each( function() {
                if(this.get('selected')){
                    value = this.get('value');
                }
            });
        }
        return value;
    }
    
    function showError(action, message){
        Y.all('.ajax_notification_'+action).empty().append(message).removeClass('success').addClass('error').addClass('show');
    }
    function showNotification(action, message){
        Y.all('.ajax_notification_'+action).empty().append(message).removeClass('error').addClass('success').addClass('show');
    }
////////////////////////////////////////////////////////////////////////////////
///////////////////// END Business Functions ///////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    
////////////////////////////////////////////////////////////////////////////////
/////////////////////Tab 1 Functions ///////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    btncreatecourse && btncreatecourse.on('click',function(){
        if(!ajaxloading){
            var action = 'createcourse';
            var coursename = Y.one('#full-course-title').get('value');
            var courseshortname = Y.one('#short-course-title').get('value');
            var coursedesc = Y.one('#course-summary').get('value');
            if(coursename && courseshortname && coursedesc){
                var paramstring = 'sesskey='+sesskey+'&ajaxtype='+action+'&cdesc='+coursedesc+'&cname='+coursename+'&cshortname='+courseshortname;
                ajaxBegin(action);
                ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/restore_ajax.php','POST',paramstring,function(response){
                    if(response.success){
                        document.location.href = siteurl + '/course/view.php?id='+response.courseid+'&wizard=true';
                    }else{
                        showError(response.message);
                        ajaxEnd(action);
                    }
                });
            }
            else{
                showError("createcourse", "Please fill all fields.");
            }
        }
    });
   
////////////////////////////////////////////////////////////////////////////////
///////////////////// END Tab 1 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
   

////////////////////////////////////////////////////////////////////////////////
///////////////////// Tab 2 Functions //////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    //handle selection change and show corresponding resources (if any)
    sectionselect && sectionselect.on('change',function(){
        Y.all('table#currentresourcestable tr').addClass('hide');
        var selected = getSelectValue(this);
        Y.all('table#currentresourcestable tr.section_'+selected).addClass('show');
        Y.one('#scorm-content').addClass('hide');
        Y.one('#resource-content').addClass('hide');
    });
    
    //handle file selection and upload the file once a file is selected
    resourceuploadinput && resourceuploadinput.on('change',function(){
        if(!ajaxloading){
            uploadResource('resource');
        }
    });
    
    scormuploadinput && scormuploadinput.on('change',function(){
        if(!ajaxloading){
            uploadResource('scorm');
        }
    });
    
    function uploadResource(module){
        
        var section = getSelectValue(sectionselect);
        if(courseId && section){
            var form = document.getElementById('upload_'+module);
            var input;
            input = document.createElement('input');
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "sesskey");
            input.setAttribute("value", sesskey);
            form.appendChild(input);
            input = document.createElement('input');
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "module");
            input.setAttribute("value", module);
            form.appendChild(input);
            input = document.createElement('input');
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "course");
            input.setAttribute("value", courseId);
            form.appendChild(input);
            input = document.createElement('input');
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "section");
            input.setAttribute("value", section);
            form.appendChild(input);
            input = document.createElement('input');
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "type");
            input.setAttribute("value", "Files");
            form.appendChild(input);
            
            ajaxBegin('upload'+module);
            ioFileUpload(siteurl+'/course/dndupload.php',form,resourceUploaded);
        }else{
            showError("saveresource", "Courseid and Section must be set.");
        }
    }
    
    var resourceUploaded = function(response){
        if(response.error == 0){
            if(courseId){
                var paramstring = 'sesskey='+sesskey+'&ajaxtype=getresourcelistitems&cid='+courseId;
                ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(listresponse){
                    if(listresponse.success){
                        var tablebody = Y.one('table#currentresourcestable tbody');
                        tablebody && tablebody.empty().append(listresponse.message);
                        var section = getSelectValue(sectionselect);
                        var currentItems = tablebody.all('tr.section_'+section);
                        currentItems.show(true);
                    }
                    else{
                        showError("upload", "Response empty");
                    }
                });
            }
            else{
                showError("upload", "Course id is not set.");
            }
        }else{
            showError("upload", response.error);
        }
        ajaxEnd('upload');
        Y.one('#fileuploadcontainer').addClass('hide');
        Y.one('#scormuploadcontainer').addClass('hide');
        Y.one('#theTabFile').addClass('hide');
        Y.one('#theTabScorm').addClass('hide');
    };
    
    //resource item click
    //this click will fire an ajax request to the server to 
    //bring back all the details for the selected module
    resourcetable && resourcetable.delegate('click',function(e){
        if(!ajaxloading){
            var button = e.currentTarget;
            currentResource = button.get('id');
            //grab the mod data from the server
            var paramstring = 'sesskey='+sesskey+'&ajaxtype=getresourcedata&cid='+courseId+'&mid='+currentResource;
            ajaxBegin('editresource_'+currentResource);
            ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
                if(response.success){
                    if(button.hasClass('scorm')){
                        Y.one('div#scorm-completion-container').empty().append(response.modcompletion);
                        Y.one('div#scorm-details-container').empty().append(response.moddetails);
                        showHide('#scorm-content','#resource-content');
                    }
                    if(button.hasClass('resource')){
                        Y.one('div#resource-completion-container').empty().append(response.modcompletion);
                        Y.one('div#resource-details-container').empty().append(response.moddetails);
                        showHide('#resource-content','#scorm-content');
                    }
                }
                else{
                    showError(response.message);
                }
                ajaxEnd('editresource_'+currentResource);
            });
        }
    },'.btn_edit_mod');
    
    
    updateModuleButtons && updateModuleButtons.on('click',function(e){
        if(!ajaxloading){
            var btn = e.currentTarget;
            var type = btn.get('id').split("-")[1];
            var paramstring = 'sesskey='+sesskey+'&ajaxtype=updateresource&cid='+courseId+'&mid='+currentResource;
            //'&mod_name='+modname+'&mod_desc='+moddesc+'&completion='+completion+'&view_completion='+viewCompletion
            Y.all('#'+type+'-content input:not([type="button"])').each(function(node) { 
                var name = node.get('name');
                var type = node.get('type');
                var value;
                if(type === 'checkbox')
                   value = node.get('checked');
                else
                   value = node.get('value');
                paramstring += '&'+name+'='+value;
            });
            Y.all('#'+type+'-content textarea').each(function(node){
                var name = node.get('name');
                var value = node.get('value');
                paramstring += '&'+name+'='+value;
            });
            Y.all('#'+type+'-content select').each(function(node){
                var name = node.get('name');
                var value = getSelectValue(node);
                paramstring += '&'+name+'='+value;
            });
            ajaxBegin('saveresource');
            ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
                if(response.success){
                    Y.one('table#currentresourcestable tr#'+currentResource+' .mod_name').empty().append(response.name);
                    showNotification("Resource updated");
                }
                else{
                    showError(response.message);
                }
                ajaxEnd('saveresource');
            });
        }
    });
    
    
    scormCompletionSelect && scormCompletionSelect.delegate('change',function(e){
        if(getSelectValue(e.currentTarget) > 1){
            Y.all('#scorm-completion-container input').set('disabled',false);
        }
        else{
            Y.all('#scorm-completion-container input').set('disabled',true);
            Y.all('#scorm-completion-container input[type="checkbox"]').set('checked',false);
            Y.all('#scorm-completion-container input[type="text"]').set('value','');
        }
    },'.sel_completion');
    
    
    resourceCompletionSelect && resourceCompletionSelect.delegate('change',function(e){
        if(getSelectValue(e.currentTarget) > 1){
            Y.all('#resource-completion-container input').set('disabled',false);
        }
        else{
            Y.all('#resource-completion-container input').set('disabled',true);
            Y.all('#resource-completion-container input[type="checkbox"]').set('checked',false);
        }
    },'.sel_completion');
    
    
    //SCORM tab click
    tabaddscorm && tabaddscorm.on('click',function(){
        showHide('#scormuploadcontainer','#fileuploadcontainer',false);
        Y.one('#scorm-content').addClass('hide');
        Y.one('#resource-content').addClass('hide');
    });
    
    //File tab click
    
    tabaddfile && tabaddfile.on('click',function(){
        showHide('#fileuploadcontainer','#scormuploadcontainer',false);
        Y.one('#scorm-content').addClass('hide');
        Y.one('#resource-content').addClass('hide');
    });
    //END add a file tab
    
////////////////////////////////////////////////////////////////////////////////
///////////////////// END Tab 2 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
///////////////////////// Tab 3 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    
    btnsavecompletion && btnsavecompletion.on('click',function(){
        if(!ajaxloading){
            var oa = Y.one('#id_overall_aggregation').get('value');
            var criteria_activities = new Array();
            Y.all('div#activity-completion-container input[type="checkbox"]').each(function(node){
                var value = node.get('checked') ? 1 : 0;
                if(value !== 0){
                    var criteria_activity = new Object();
                    var id = node.get('id');
                    criteria_activity.id = id;
                    criteria_activity.value = value;
                    criteria_activities.push(criteria_activity);
                }
            });
            var ca = JSON.stringify(criteria_activities);
            var aa = Y.one('#id_activity_aggregation').get('value');
            var paramstring = 'sesskey='+sesskey+'&ajaxtype=setcompletionstatus&cid='+courseId+'&ca='+ca+'&oa='+oa+'&aa='+aa;
            ajaxBegin('savecompletion');
            ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
                if(response.success){
                    showNotification("savecompletion", response.message);
                }
                else{
                    showError("savecompletion", "Unable to save details");
                }
                ajaxEnd('savecompletion');
            });
        }
    });

////////////////////////////////////////////////////////////////////////////////
///////////////////// END Tab 3 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
///////////////////////// Tab 4 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    function loadEnrolementTable(enrolled){
        var paramstring;
        var targetcontainer;
        if(enrolled){
            paramstring = 'sesskey='+sesskey+'&ajaxtype=getenrolledusers&cid='+courseId;
            targetcontainer = Y.one('#enrolled-users-container');
            
        }else{
            paramstring = 'sesskey='+sesskey+'&ajaxtype=getunenrolledusers&cid='+courseId;
            targetcontainer = Y.one('#unenrolled-users-container');
        }
        ajaxBegin('loadenroltable');
        ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
            if(response.success){
                targetcontainer.empty().append(response.message);
            }
            else{
                showError(response.message);
            }
            ajaxEnd('loadenroltable');
        });
    }
    
    enrolledusertable && enrolledusertable.delegate('change',function(e){
        var cb = e.currentTarget;
        if(cb.get('id') == 'chkallusr'){
            Y.all('#enrolledusertable input[type="checkbox"]').set('checked',cb.get('checked'));
        }
    },
    'input[type="checkbox"]');
    
    
    unenrolledusertable && unenrolledusertable.delegate('change',function(e){
        var cb = e.currentTarget;
        if(cb.get('id') == 'chkallusr'){
            Y.all('#unenrolledusertable input[type="checkbox"]').set('checked',cb.get('checked'));
        }
    },
    'input[type="checkbox"]');
    
    
    btnenrolusers && btnenrolusers.on('click',function(){
        if(!ajaxloading){
            var usersstring = '';
            Y.all('#unenrolledusertable input[type="checkbox"]').each(function(node){
                if(node.get('id') != 'chkallusr'){
                    if(node.get('checked')){
                        usersstring += node.get('id');
                        usersstring += '-';
                    }
                }
            });
            if(usersstring !== ''){
                //remove last '-'
                usersstring = usersstring.substring(0, usersstring.length - 1);
                var paramstring = 'sesskey='+sesskey+'&ajaxtype=enrolusers&cid='+courseId+'&users='+usersstring;
                ajaxBegin('enrolusers');
                ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
                    if(response.success){
                        loadEnrolementTable(true);
                        loadEnrolementTable(false);
                    }
                    else{
                        showError(response.message);
                    }
                    ajaxEnd('enrolusers');
                });
            }
        }
    });


////////////////////////////////////////////////////////////////////////////////
///////////////////// END Tab 4 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
///////////////////////// Tab 5 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    btnpublishcourse && btnpublishcourse.on('click',function(e){
        e.preventDefault();
        if(!ajaxloading){
            var category = getSelectValue(Y.one('#categorySelect'));
            var paramstring = 'sesskey='+sesskey+'&ajaxtype=publishcourse&cid='+courseId+'&catid='+category;
            ajaxBegin('publishcourse');
            ajaxRequest(siteurl+'/local/lp_coursewizard/ajax/proxy.php','POST',paramstring,function(response){
                if(response.success){
                    showNotification('publishcourse',response.message);
                }
                else{
                    showError('publishcourse',response.message);
                }
                ajaxEnd('publishcourse');
            });
        }
    });

////////////////////////////////////////////////////////////////////////////////
///////////////////// END Tab 5 Functions //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
////////////////////////  Tab View functions  //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    tabControls && tabControls.on('click',function(e){
        var tab = e.currentTarget;
        wrapper.removeClass('hide');        
        tabControls.removeClass('selected');
        tab.addClass('selected');
        var target = tab.get('id');
        if(target !== 'tab1' && currentTab !== target){
            showHide('div#tabs-container div.tabs-content div#'+target,'div#tabs-container div.tabs-content div#'+currentTab);
            currentTab = target;
        }
    });
    
////////////////////////////////////////////////////////////////////////////////
/////////////////////  END Tab View functions  /////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
        
        
    //used to show/hide the wizard
    togglebutton && togglebutton.on('click',function(){
        wrapper.toggleClass('visible').removeClass('hide');
        if(wrapper.hasClass('visible')){
            togglebutton.set('text', 'Close Course Wizard').addClass('active');
        }
        else{
            togglebutton.set('text', 'Launch Course Wizard').removeClass('active');
        }
    });
    //end toggle button

    //used to show/hide the wizard
    closeBtn && closeBtn.on('click',function(){
        wrapper.toggleClass('visible').removeClass('hide');
        if(wrapper.hasClass('visible')){
            togglebutton.set('text', 'Close Course Wizard').addClass('active');
        }
        else{
            togglebutton.set('text', 'Launch Course Wizard').removeClass('active');
        }
    });
    //end toggle button

    //used to minimise the wizard
    minimiseBtn && minimiseBtn.on('click',function(){
        wrapper.toggleClass('hide');
    });
    //end toggle button
};
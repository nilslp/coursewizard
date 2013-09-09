M.local_dlelegacytools_lpscheduler_welcomeemail = {};

M.local_dlelegacytools_lpscheduler_welcomeemail.init = function(Y) {
 var ms = Y.one('#id_self_reg_welcome_link'),
 t = Y.one('#id_self_reg_confirmation_text'); //reduce the number of var's to one use comma to seperate
    
    ms.on('change',function(e){ //the param passed is the event or 'e'
    
       var index = Y.one('#id_self_reg_welcome_link').get('selectedIndex'),
       value = Y.one("#id_self_reg_welcome_link").get("value"); //YUI normalises the select so use '.get()'
          
        if(value == "false"){
            t.setAttribute('disabled','disabled');
        }else{
            t.removeAttribute('disabled');
        }
    });
}

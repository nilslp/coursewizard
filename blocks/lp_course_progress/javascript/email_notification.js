M.block_lp_course_progress = {};

M.block_lp_course_progress.init = function(Y) {
    var calendar = false;
    var panel = false;
    var form = false;
    var calendartarget = false;
    var toggleend = false;
    var addcourse = false;
    var removecourse = false;
    var YAHOO = Y.YUI2;
    
    noti = Y.one('#notification');
    noti && noti.delegate(
        'click',
        function(e){
            var show = false;
            var table = false;
            if (e.currentTarget.hasClass('calendar')){
                calendartarget = e.currentTarget.get('id');
                calendartarget && (calendartarget=calendartarget.substr(calendartarget.indexOf('_')+1));
                
                if (!calendar){
                    calendar = new YAHOO.widget.Calendar('calendar');
                    calendar && calendar.render(panel);
                }
                
                if (panel){
                    if (calendartarget == 'enddate' && toggleend && toggleend.get('checked')) {
                        // do nothing
                    } else {                        
                        panel.moveTo(e.pageX+10,e.pageY-50);
                        panel.show();
                    }
                }

                if (calendar){
                    calendar.selectEvent.subscribe(updateDate);
                }
            } else if (e.currentTarget.hasClass('limitcourse')){
                show = parseInt(e.currentTarget.get('value'),10);
                table = Y.one('#course_select_table');
                if (!table){
                    return;
                }
                if (show){
                    table.show();
                } else {
                    table.hide();
                }
            }
        },
        'button.calendar,input.limitcourse'
    );
        
    function updateDate(e, dates) {
        var d = dates[0][0];
        var input = Y.one('#'+calendartarget);
        input && input.set('value',d[2]+'-'+d[1]+'-'+d[0]);
        panel && panel.hide();
        calendar.selectEvent.unsubscribe(updateDate);
    }
   
    // Create the main modal form.
   panel = new YAHOO.widget.Panel("calendarpanel", {
                //width: "240px",
                fixedcenter: true,
                close: true,
                draggable: true,
                zindex: 4,
                //modal: true,
                visible: false
            });
    panel.render();
    
    // checkbox behavior
    toggleend = Y.one('#toggleend');
    toggleend && toggleend.on('change',function(e){
        var btn = Y.one('#btn_enddate');
        var textfield = Y.one('#enddate');
        if (btn){
            if (this.get('checked')){
                btn.set('disabled','disabled');
                textfield.set('value','');
            } else {
                btn.set('disabled',false);
            }
        }
    });
            
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
        
        var list = from.all('option');
        for (iter = 0; iter < list.size(); ++iter){
            obj = list.item(iter);
            if (obj && obj.get('selected')){
                to.append(obj);
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
    
}

function validateForm(){
	var frm = document.getElementById('notification_form');
    
	if ("" == frm.description.value){
		alert("You must provide a description.");
		frm.description.focus();
		return false;
	}
    
	if ("" == frm.message.value){
		alert("You must provide the email message text.");
		frm.message.focus();
		return false;
	}
        
	return frm.submit();
}

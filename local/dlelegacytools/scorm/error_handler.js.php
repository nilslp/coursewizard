<?php
//extra stuff here
?>
var fnWinErrHndl=function(msg, url, line){
	             	if(typeof console !== 'undefined'){
	             		//console.log('error occurred :: ' + msg + ' : ' + line + ' : ' + url);
	             	}else if(window.top.gError){
	             		window.top.gError('error occurred :: ' + msg + '\n' + line + '\n' + url);
	             	}
	             	return false;
				};
				 
window.onerror=fnWinErrHndl;

var ldhndl_pass=(function(){
	var rtn=function(){
		var resp;
		if(!ldhndl_pass.win_load && ply){
			//console.log('hndl called');
			if(ply.contentWindow){
				
				resp=_scorm_wrap(ply.contentWindow);
				//console.log('Wrap resp = ' + resp);
				if(resp === true){
					_init_check(ply.contentWindow);
				}else if(resp===-3){
					setTimeout(function(){
						ldhndl_pass.win_load=false;
						ldhndl_pass();
					},50);
				}
				
			}else{
				//console.log('no contentWindow');
			}
		}
		ldhndl_pass.win_load=true;
		var i=0,l=ldhndl_pass.list.length;
		if(l>0){
			for(;i<l;i++){
				pass_call(ldhndl_pass.list[i].name,ldhndl_pass.list[i].args);
			}
		}
	};
	
	rtn.win_load=false;
	
	rtn.timer_hndl=0;
	
	rtn.list=[];
	
	rtn.queue=function(fnName,args){
		ldhndl_pass.list[ldhndl_pass.list.length]={name:fnName,'args':args};
		//ldhndl_pass.timer_hndl=setTimeout(ldhndl_pass,40);
	};
	
	return rtn;
}());

function gError(msg){
	alert(msg);
}

function _init_check(win) {
		
	var _clos = function(window) {
		return function(){
		if(confirm("Are you sure you wish to exit the Module?")) { //'want' has been deliberately swapped for 'wish' as an unobtrusive confirmation code has been hijacked
			if(window === top) {
				window.close();
			} else if( typeof top.opener !== 'undefined' && top.opener !== null) {
				top.close();
			} else if(window.location.href.indexOf('file://') === 0) {
				window.close();
			}
		}}
	}(win);
	if(typeof win.__lp_tester === 'undefined' || typeof win.__lp_tester.version === 'undefined'){
		//console.log('Tester is not loaded');
		setTimeout(function(){
			if(_scorm_wrap(win)){
				_init_check(win);
			}
		},20);
	}else{
		//console.log('PAYLOAD tester came back with');
		//console.log('v:' + win.__lp_tester.version);
		if(win.__lp_tester.version !== -1 ){
			if(win.__lp_tester.version < 6) {
				win.course_close = _clos;
				win.parent.course_close = _clos; //needed for multiscos
				if(win.AT_NS){
					win.AT_NS.isParent=function(){
						if(typeof _isParent === 'undefined' && typeof i !== 'undefined'){
							i=(!(typeof iLP === 'undefined'));
							this.isParent=function(){return i;};
						}else if(typeof _isParent !== 'undefined' ){
							_isParent=(!(typeof iLP === 'undefined'));
							this.isParent=function(){return _isParent;};
						}	
					};
					win.AT_NS.isParent();
				}
			} else if(win.__lp_tester.version < 7) {
				win.cnav.course_close = _clos;
				win.parent.course_close = _clos; //needed for multiscos
				win.AT_NS.isParent=function(){
					if(typeof _isParent === 'undefined' && typeof i !== 'undefined'){
						i=(!(typeof iLP === 'undefined'));
						this.isParent=function(){return i;};
					}else if(typeof _isParent !== 'undefined' ){
						_isParent=(!(typeof iLP === 'undefined'));
						this.isParent=function(){return _isParent;};
					}	
				};
				win.AT_NS.isParent();
			}else if(win.__lp_tester.version < 8){
				fix_resize(win);
			}	
		}else if(win.closeThis){
			win.closeThis=function(){
				if( typeof top.opener !== 'undefined' && top.opener !== null) {
					top.close();
				}
			}
		}else{
            win.close=function(){
                    if( typeof top.opener !== 'undefined' && top.opener !== null) {
                            top.close();
                    }
            };
        }

	}
}

function _scorm_wrap(win){
	//console.log('Wrap called');
	//check if the target window already has a result attached
	if(win.__lp_tester){
		//console.log('Wrap already done');
		return true;
	}
	
	var _clos = null, iBdone = false,_minime=null;

	function _exists(a) {
		return ( typeof a !== 'undefined' && a !== null);
	}

	function __lp_ctest(obj) {
		//console.log('Test');
		if(typeof obj.__lp_tester === 'undefined'){
			if(obj.document && !(obj.document.readyState ==='complete' || obj.document.readyState ==='interactive')){
				//console.log('Window not ready:' + obj.document.readyState);
				return -3;
			}
		}
		
		if( typeof obj !== 'undefined' && typeof obj.__lp_tester === 'undefined') {
			obj.__lp_tester = ( function() {
				function _init() {
					var _tlp = {};
					_tlp.version=-1;
					
					if((_exists(obj.lowerButtons) && _exists(obj.znThisPage) && _exists(obj.znPagesTot)) || (_exists(obj.znMaxPage) && _exists(obj.znTotPage) && _exists(obj.course_close))) {
						_tlp.version = 4;
					}

					if(_exists(obj.cnav)) {
						if(_exists(obj.AT_NS.version)) {
							_tlp.version = obj.AT_NS.version();
						} else {
							_tlp.version = 6;
						}
					} else if(_exists(obj._manifest) && _exists(obj.bBookmark)) {
						_tlp.version = 5;
					}
					//console.log('version::' + _tlp.version);
					return _tlp;
				}

				return _init();
			}());
		}else if(typeof obj.__lp_tester !== 'undefined'){
			return true;
		}
	}
	
	if(_exists(win)){
		__lp_ctest(win);
		return true;
	}else{
		//console.log('Bad Window Passed');
		return false;
	}
			
}

function fix_resize(wn){
	var win=wn;
	
	win.getDocHeight=(function(wn) {
		var D = wn.document;
		return function(){
			return Math.max(
			        Math.max(D.body.scrollHeight, D.documentElement.scrollHeight),
			        Math.max(D.body.offsetHeight, D.documentElement.offsetHeight),
			        Math.max(D.body.clientHeight, D.documentElement.clientHeight)
			);
		}
	}(win));
	
	win.resize_guard= function(win){
		var rg_mfrm=win.document.getElementById('mainFrame'),
			rg_win=win,
			rg_timeout=0;
		if(rg_mfrm && rg_win){
			win.resize_guard = function(){
				var rfrmh = parseInt(rg_mfrm.getAttribute('height'));
				if(rfrmh > screen.height || rfrmh < 400){
					rg_mfrm.setAttribute('height',screen.height-250);
				}
				if(rg_timeout===0){
					rg_timeout=rg_win.setTimeout(rg_win.resize_guard,200);
				}else{
					clearTimeout(rg_timeout);
					rg_timeout=0;
				}
			}
			win.resize_guard();
		}
	}
	
	fix_resize = function(wn){
		var tmOut=0,win=wn;
		
		win.onresize = function(){
			clearTimeout(tmOut);
			
			tmOut=setTimeout(function(){
				if(!win.resize_guard){
					if(win.console){
						console.log('Resize Guard missing');
					}
					return false;
				}
				
				win.resize_guard(win);
							
			},200);
		}
		
		
	};
	
	fix_resize(win);
	
	return true;
	
	win.YUI().use('node','event',function(Y){

		win.resizer=function(ev){
			//Y.log('Resize hijacked');
			var ftr=Y.one('#stdfooter'), frm=Y.one('#mainFrame'),
				pg=Y.one('#pageWrapperID'), hd=Y.one('#header'),
				wH,oWH, iOff=0;
		
			if(!frm){
				return false;
			}

			//Y.log('Starting height of frame = ' + frm.get('height'));
			//Y.log('Window innerHeight = ' + win.innerHeight + ' :: outerHeight = ' + win.outerHeight);

			/* Check to see if the accessibility styles have kicked in */
			if(!ftr || ftr.getStyle('display')==="none"){
				ftr=Y.one('#footerID');
				//iOff=hd.getY();
				hd=null;
			}
			 	
			iOff=(pg.get('offsetHeight') - pg.get('clientHeight'))+10;
			//Y.log('Resize offset = ' + iOff);
			if(!ftr){
				return false;
			}

			//wH = parseInt(Y.one("body").get("winHeight"),10);
			oWH = wH = win.getDocHeight();
			
			//Y.log('WinHeight = ' + wH);
			
			if( wH < 400 ){ //don't want to shrink it too far
				wH = 400;
			}
			
			//header
			if(hd){
				wH -= hd.get('offsetHeight');
			}

			//footer
			wH -= ftr.get('offsetHeight');

			//iOff
			wH -= iOff;

			//Y.log('Result winHeight = ' + wH);

			//wH=(wH-(parseInt(frm.getY(),10) + parseInt(ftr.getComputedStyle('height'),10) + iOff));
			if(wH > oWH){
				Y.later(200,win,win.resizer);
				return false;
			}
		
			setTimeout(function(){
				//Y.log('Set this to ' + wH);
				frm.set('height',wH);
			},50);			
	
			//frm.set('height',wH);
		};

		Y.one('win').purge(false,'resize');
		
		Y.one('win').after('resize',win.resizer,win);

	});

}

function handle_frames(win,attch){
	var i=0;
	if(win !== top){
		win.errorHndl=function(msg, url, line){
		             	if(window.top.gError){
		             		window.top.gError(msg);
		             	}
		             	return false;
					};
	    win.onerror=win.errorHndl;
	}
	for(i=0;i<win.frames.length;i++){
		if(attch){
			var ld_hndl=function(){
				win.handle_frames(this,true);
				return true;
			}
			if(win.frames[i].addEventListener){
				win.frames[i].addEventListener('load',ld_hndl);	
			}else if(win.frames[i].attachEvent){
				win.frames[i].attachEvent('onload',ld_hndl);
			}
				
		}
		
  		handle_frames(win.frames[i],false);
  	}
}

function check4frameset(win){
	var i=0;fs=win.document.getElementsByTagName('frameset'),l=fs.length;
	if(l !== 0){
		//debugger;
		for(;i<l;i++){
			
		}
	}
}

YUI().use('event',function(Y){
	Y.on('load',function(){
		var o=Y.one('#scorm_object');
		if(o){
			//console.log('Attached the event hndl');
			if(o._node.contentDocument && o._node.contentDocument.readyState && o._node.contentDocument.readyState==="complete" && o._node.contentDocument.location.href.indexOf('index.') > -1){
				//if(o._node.contentWindow && !check4frameset(o._node.contentWindow)){
					//console.log('Go for gold');
					setTimeout(ldhndl_pass,300);
				//}
			}else if(o._node.contentDocument && o._node.contentDocument.readyState && o._node.contentDocument.readyState==="complete" && o._node.contentDocument.location.href.indexOf('index.') == -1){
				//in loadSco still
				//console.log('Go for silver');
				o._node.contentWindow.onbeforeunload=function(){
					//Not needed just now
					//o._node.onload=ldhndl_pass;
				};
			}
			o._node.onload=ldhndl_pass;
			o.on('load',ldhndl_pass,o);
			
		}else{
			//console.log('no object');
		}
	});
});

function pass_call(fnName,args){
	var wn=null;
	
	wn=find_function(window,fnName);
	
	if(wn===null){
		wn=find_function(document.getElementById('scorm_object').contentDocument,fnName);
	}
	
	if(wn!==null){
		return wn[fnName].apply(wn[fnName],args);
	}else if(ldhndl_pass.win_load===false){
		ldhndl_pass.list[ldhndl_pass.list.length]={name:fnName,'args':args};
	}else{
		ldhndl_pass.queue(fnName,args);
	}
	
	return null;	
}

function find_function(win,fnName){
	var i=0,wn=null;
	
	for(i=0;i<win.frames.length;i++){
		
		if(win.frames[i][fnName]){
			return win.frames[i];
		}
		
		wn=find_function(win.frames[i],fnName);
		
		if(wn!==null){
			return wn;
		}
	}
	
	return wn;
}

var create_passer=(function(){
	
	function _c_p(fnN){
		var myName=fnN;
		return function(){
			pass_call(myName,arguments);			
		}
	}
	
	return function(fnName){
		if(typeof window.top[fnName] === 'undefined'){
			window.top[fnName]=_c_p(fnName);
		}
	}
	
}())


var ply_hnd,ply=document.getElementById('scorm_object');
if(ply){
	
	ply_hnd=(function(p){
		var _ply=p;
		return function(){
			try{
				if(_ply.contentDocument.location.href.indexOf('index.') > -1){
					//console.log('frame handle');
					handle_frames(window,true);
					return true;						
				}				
			}catch(e){
				////console.log(e);
			}
			setTimeout(ply_hnd,40);
		}
	}(ply));

	setTimeout(ply_hnd,40);

	create_passer('SetThisPage');
	create_passer('LoadMenuPage');
	create_passer('LoadUpTheLMS');
}

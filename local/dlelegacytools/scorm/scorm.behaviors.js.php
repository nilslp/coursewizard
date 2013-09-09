<?php
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

$strarg = new stdClass();
$strarg->score      = optional_param('score','',PARAM_INT);
$strarg->scoremax   = optional_param('scoremax','',PARAM_INT);
$strarg->title      = optional_param('scormtitle','',PARAM_TEXT);
$popup              = optional_param('popup',0,PARAM_INT);
$course             = optional_param('course','',PARAM_INT);
#$obj           = optional_param('obj','',PARAM_ALPHANUMEXT); // use later?

// get our configuration settings
$scormcfg = array();
$scormcfg['popup']          = get_config('local/dlelegacytools', 'scormpopup');
$scormcfg['scoremsg']       = get_config('local/dlelegacytools', 'scormscoremessage');
$scormcfg['scormredirect']  = get_config('local/dlelegacytools', 'scormredirect');

$popupmsg = get_string('scormpopupmsg', 'local_dlelegacytools');
if (!empty($strarg->scoremax) && !empty($scormcfg['scoremsg']) && !$popup){
    $title = get_string('sp'.$scormcfg['scoremsg'],'local_dlelegacytools',$strarg);
}

// check display mode
if ($popup){
    $scormcfg['popup'] = 'hide';
}

// set redirect if enabled
switch ($scormcfg['scormredirect']){
    case 'homepage':
        $scormcfg['scormredirect'] = $CFG->wwwroot;
        break;
    case 'coursetopic':
        $scormcfg['scormredirect'] = $CFG->wwwroot.'/course/view.php?id='.$course;
        break;
    case 'coursecatlist':
        $scormcfg['scormredirect'] = $CFG->wwwroot.'/course/index.php';
        break;
    case 'coursecatpage':
        $category = $DB->get_field('course','category',array('id'=>$course));
        $scormcfg['scormredirect'] = $CFG->wwwroot.'/course/category.php?id='.$category;
        break;
    default:
        $scormcfg['scormredirect'] = '';
        break;
}


$scormcfg = json_encode($scormcfg);

echo <<<JSCRIPT
var scormcfg = $scormcfg;
/** event stuff **/
	var NORM = (function() {
		var ready=false, bindList = [], normon = null,DOMContentLoaded,hkd_popup=null;
		function doScrollCheck() {
			if(normon.isReady) {
				return;
			}
			try {
				// If IE is used, use the trick by Diego Perini
				// http://javascript.nwbox.com/IEContentLoaded/
				document.documentElement.doScroll("left");
			} catch(e) {
				setTimeout(doScrollCheck, 1);
				return;
			}
			// and execute any waiting functions
			normon.ready();
		}

		// Cleanup functions for the document ready method
		if(document.addEventListener) {
			DOMContentLoaded = function() {
				document.removeEventListener("DOMContentLoaded", DOMContentLoaded, false);
				normon.ready();
			};
		} else if(document.attachEvent) {
			DOMContentLoaded = function() {
				// Make sure body exists, at least, in case IE gets a little overzealous (ticket #5443).
				if(document.readyState === "complete") {
					document.detachEvent("onreadystatechange", DOMContentLoaded);
					normon.ready();
				}
			};
		}
		normon = {
			isReady : false,
			ready : function() {
				var y;
				normon.isReady=true;
				if(bindList.length > 0) {
					for( y = 0; y < bindList.length; y += 1) {
						bindList[y]();
					}
				}
			},
			bindReady : function() {
				if(ready) {
					return;
				}
				ready = true;

				// Catch cases where \$(document).ready() is called after the
				// browser event has already occurred.
				if(document.readyState === "complete") {
					// Handle it asynchronously to allow scripts the opportunity to delay ready
					return setTimeout(normon.ready, 1);
				}

				// Mozilla, Opera and webkit nightlies currently support this event
				if(document.addEventListener) {
					// Use the handy event callback
					document.addEventListener("DOMContentLoaded", DOMContentLoaded, false);
					// A fallback to window.onload, that will always work
					window.addEventListener("load", normon.ready, false);
					// If IE event model is used
				} else if(document.attachEvent) {
					// ensure firing before onload,
					// maybe late but safe also for iframes
					document.attachEvent("onreadystatechange", DOMContentLoaded);

					// A fallback to window.onload, that will always work
					window.attachEvent("onload", normon.ready);

					// If IE and not a frame
					// continually check to see if the document is ready
					var toplevel = false;

					try {
						toplevel = window.frameElement == null;
					} catch(e) {
					}

					if(document.documentElement.doScroll && toplevel) {
						doScrollCheck();
					}
				}
			},
			/**normalise the event attach statement**/
			attachAnEvent:function(obj,evt,fn,bub){
				if(obj.addEventListener) {
					obj.addEventListener(evt,fn,bub);
					return true;
				}else if (obj.attachEvent) {
			        return obj.attachEvent('on' + evt, fn);
		     	} else {
		        	obj['on' + evt] = (function(){
		        		var oldevt;	
		        		return function(){
		        			if(typeof oldevt==='function'){
		        				oldevt();
							}
							fn();
						};
					}(obj['on' + evt]));
					return true;
		     	}
			},
			/** add function to be called on DOMcontentloaded **/ 
			onReady:function(fn){
				if(!normon.ready){
					 bindList.push(fn);
				}else{
					/** too late its already happened so fire **/
					setTimeout(fn,1);
				}
			}
		};
		
		return normon;
	}());
/** end event **/
// check for popup window
if (typeof windowobj !== 'undefined' && windowobj){
    if (!windowobj.closed && windowobj.innerHeight){  // chrome workaround
        // no need to show msg
        scormcfg.popup = 'hide';
    }

    // listen to close event and fire off the redirect
    //windowobj.addEventListener('unload',function(e){
    NORM.attachAnEvent(windowobj,'unload',function(e){
        scormcfg.scormredirect && setTimeout(function(){
                windowobj.closed && (window.location=scormcfg.scormredirect);
            },
            1000
        );
    },false);
}

	function _add_sldr(h){
		try{
			var tg=h.document.createElement('script');
	                tg.setAttribute('type','text/javascript');
        	        tg.setAttribute('src','$CFG->wwwroot/local/dlelegacytools/scorm/scorm.loader.js');
                	h.document.body.appendChild(tg);
					//console.log('Injected the payload');
			return true;
		}catch(e){
			//alert('Could not create script tag ' + e.message);
			return false;
		}
	}

	function _hooke_crs(hold){
		var _hold=hold;
		if(_add_sldr(_hold)===false){
			var _hld_hnd=setInterval(function(){if(_add_sldr(_hold)){clearInterval(_hld_hnd);}},30);
		}
	}

    var el = {},
    title = '$title',hold=null;

    // handle popup blocker message
    if ('show' == scormcfg.popup){
        el = document.getElementById('scormpage');
        var msg = document.createElement('div');
        msg.setAttribute("class","notifyfailure notifyproblem");
        msg.innerHTML="$popupmsg";
        el && el.insertBefore( msg );
    }

    // render score message
    if (title){
        el = document.getElementById('scormpage');
        var scoremsg = document.createElement('div');
        scoremsg.setAttribute("class","box generalbox");
        scoremsg.innerHTML=title;
        el && el.insertBefore( scoremsg );
    }
JSCRIPT;

( function() {
	if( typeof window.parent.NORM !== 'undefined') {
		var _scorm_m2_wrap=(function() {
			//console.log('Payload load event');
			var _clos = null, iBdone = false,_minime=null;

			function _exists(a) {
				return ( typeof a !== 'undefined' && a !== null);
			}

			function __lp_ctest(obj) {
				if( typeof obj !== 'undefined' && typeof obj.__lp_tester === 'undefined') {
					obj.__lp_tester = ( function() {
						var _tlp = {};
						function _init() {
							if(_exists(obj.lowerButtons) && _exists(obj.znThisPage) && _exists(obj.znPagesTot)) {
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
							return _tlp;
						}

						return _init();
					}());
				}
			}

			function _init_chk() {
				//console.log('PAYLOAD init_check');
				if(iBdone === true) {
					return true;
				}
				//console.log('PAYLOAD init_check -go');
				__lp_ctest(window);
				_clos = function() {
					if(confirm("Are you sure you wish to exit the Module?")) { //'want' has been deliberately swapped for 'wish' as an unobtrusive confirmation code has been hijacked
						if(window === top) {
							window.close();
						} else if( typeof top.opener !== 'undefined' && top.opener !== null) {
							top.close();
						} else if(window.location.href.indexOf('file://') === 0) {
							window.close();
						}
					}
				};
				//console.log('PAYLOAD tester came back with v:' + window.__lp_tester.version);
				if(window.__lp_tester.version < 6) {
					window.course_close = _clos;
				} else if(window.__lp_tester.version < 7) {
					window.cnav.course_close = _clos;
				}
				iBdone = true;
			}
			_minime=function(){
				setTimeout(_init_chk,80);
				//console.log('Timeout set');
			};
			_minime.initialise=function(){
				_init_chk();
			};
			_minime.exists=function(v){
				return _exists(v);
			};

			return _minime;
		}());
		_scorm_m2_wrap();
	}
}());
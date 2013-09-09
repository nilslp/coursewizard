M.block_lp_course_progress = {};

M.block_lp_course_progress.init = function(Y) {
    
    var course_categories = new Array();
    var course_progress_block = Y.one('.block_lp_course_progress');
    var centre_area = Y.one('#region-main');
    var default_show = 'incomplete';
    var html_overlay = '<div id="dle-overlay"></div>';
    
    // Check if the block has been rendered in 'region-main'.  This would happen
    // if the block was added to 'My Page '
    if (centre_area) {
        if (centre_area.contains(course_progress_block)) {
            // Make the AJAX call to get the content
            Y.io('/blocks/lp_course_progress/full_course_progress.php?embedded=true', {
                on:   {success: completeInPage}
            });
        }
        else {
            // Add Overlay Object
            // Y.one('body').prepend(html_overlay);
            var dle_overlay = new Y.Panel({
                //srcNode: '#dle-overlay',
                srcNode: Y.Node.create('<div id="my-course-progress-panel"></div>'),
                visible: false,
                width: '80%',
                height: '80%',
                centered: true,
                constrain: true,
                render: true,
                modal: true,
                zIndex: 1100,
                headerContent: '<h2>' + M.util.get_string('blocktitle', 'block_lp_course_progress') + '</h2><div class="meter-wrap"></div>',
                bodyContent: '<div class="wait">Loading</div>',
                footerContent: '',
                buttons: [
                        {
                            value: 'Close',
                            action: function(e) {
                                    e.preventDefault();
                                    dle_overlay.hide();
                                },
                            section: Y.WidgetStdMod.FOOTER,
                            classNames: 'close'
                        }
                    ]
            });
            cpOverlayInit();
        }
    }   
    
    cpInit();
   
    // Define a function to handle the response data.
    function completeOverlay(id, o, args) {
        dle_overlay.show();
        var data = o.responseText; // Response data.
        var content = Y.one('#my-course-progress-panel.yui3-panel-content .yui3-widget-bd');
        if (content) {
            content.setContent(data);
            pb_h = Math.floor(parseFloat(Y.one('#my-course-progress-panel .yui3-widget-bd').getComputedStyle('height')));
            meter_h = Math.ceil(parseFloat(Y.one('#my-course-progress-panel .yui3-widget-bd .cp-header').getComputedStyle('height')));
            Y.one('#my-course-progress-panel .yui3-widget-bd .cp-report').setStyle('height',(pb_h - meter_h)+'px');
            
        }
        cpInit();
    }
   
    // Define a function to handle the response data.
    function completeInPage(id, o, args) {
        var data = o.responseText; // Response data.
        var content = Y.one('.block_lp_course_progress div.content');
        if (content) content.setContent(data);
        cpInit();
    }
    
    function cpOverlayInit() {
    
        Y.all('.block-region .block_lp_course_progress .content .meter a').on('click', function(e) { e.preventDefault(); });
        
        Y.all('.block-region .block_lp_course_progress .content .meter')
            .on('click', function(e) {
                Y.io('/blocks/lp_course_progress/full_course_progress.php?embedded=true&header=true', {
                    on:   {success: completeOverlay}
                });
                // dle_overlay.show();
            });
        
        // Y.all('.yui3-panel-content a.close').on('click',function() { dle_overlay.hide(); });
    
    } // end cpOverlayInit()
    
    function cpInit() { // Progress Status
        
        // Set Active Filter Tab
        Y.all('#cp-detail .filters .group li')
            .each( function(node) {
                if (node.hasClass(default_show)) {
                    node.addClass('active');
                }
            });
        
        // Show default view
        Y.all('#cp-detail dl.group')
            .each( function(node) {
                if (node.hasClass(default_show)) {
                    node.removeClass('hide');
                }
            });
            
        // Collapse all sub-groups
        Y.all('#cp-detail .cp-report dd').addClass('hide');
        
        Y.all('#cp-detail .cp-header .meter').append('<div class="glass"></div>');
        
        cpResizeLabels();
        cpBehaviors();
        
        // Add Event to resize text labels when window resized
        //Y.one('body').on('windowresize', cpResizeLabels);
        
    } // end cpInit()
            
    function cpBehaviors() {
    
        Y.all('#cp-detail .filters .group li')
            .on(
                'hover',
                function(e) { e.target.addClass('hover'); },
                function(e) { e.target.removeClass('hover'); }
            );
            
        Y.all('#cp-detail .filters .group li')
            .on(
                'click',
                function(e) {
                
                    Y.all('#cp-detail .filters .group li').removeClass('active');
                    e.target.addClass('active');
                    
                    Y.all('#cp-detail .cp-report dl.group').addClass('hide');
                    Y.all('#cp-detail .cp-report dl.group dt').removeClass('open');
                    Y.all('#cp-detail .cp-report dl.group dd').addClass('hide');
                    
                    if (e.target.hasClass('courses')) {
                        Y.all('#cp-detail .cp-report dl.courses').removeClass('hide');
                        Y.all('#cp-detail .cp-report dl.no-courses').addClass('hide');
                    }
                    else if (e.target.hasClass('incomplete')) {
                        Y.all('#cp-detail .cp-report dl.course.incomplete').removeClass('hide');
                    }
                    else if (e.target.hasClass('not-attempted')) {
                        Y.all('#cp-detail .cp-report dl.course.not-attempted').removeClass('hide');
                    }
                    else if (e.target.hasClass('complete')) {
                        Y.all('#cp-detail .cp-report dl.course.complete').removeClass('hide');
                    }
                    else if (e.target.hasClass('not-enrolled')) {
                        Y.all('#cp-detail .cp-report dl.course.not-enrolled').removeClass('hide');
                    }
                    else if (e.target.hasClass('enrolled')) {
                        Y.all('#cp-detail .cp-report dl.course.enrolled').removeClass('hide');
                    }
                    else if (e.target.hasClass('category')) {
                        Y.all('#cp-detail .cp-report dl.category').removeClass('hide');
                    }
                    else {
                        Y.all('#cp-detail .cp-report dl.status').removeClass('hide');
                    }
                    
                    cpResizeLabels();
                }
            );        
        
        Y.all('#cp-detail .cp-report dt')
            .on('click', function(e) {
                e.target.toggleClass('open');
                if(e.target && e.target.next) {
                    var dd = e.target.next('dd');
                    dd && dd.toggleClass('hide');    
                }
                e.stopPropagation();
            });
        
        Y.all('#cp-detail .cp-report dt, #cp-detail .cp-report li')
            .on(
                'hover', 
                function(e) {e.target.addClass('hover');},
                function(e) {e.target.removeClass('hover');}
            );
        
        Y.all('#cp-detail .show-more')
            .on('click', function(e) {
                e.target.siblings('dl.course').removeClass('hide');
                e.target.remove();
            });
            
    } // end cpBehaviors()
    
    function cpResizeLabels() {
    
        dt_as = Y.all('#cp-detail .cp-report dt span, #cp-detail .cp-report li span');
        dt_as.each( function(node) {
            s = node.get('offsetWidth');
            p = parseInt(node.get('parentNode').get('offsetWidth'));
            if (node.get('parentNode').nodeName == 'DT') { p = p - 14; } else { p = p - 24; }
            m = parseInt(node.siblings('.meter').get('offsetWidth'));
            a = p - m;
            if (a <= s) { node.setStyle('width', a + 'px'); }
            else { node.setStyle('width', 'auto'); }
        });
    
    } // end resizeLabels()
    
}

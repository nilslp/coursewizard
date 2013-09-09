M.local_dlelegacytools_interface = {};

M.local_dlelegacytools_interface.init = function(Y,sessionkey) {
    var list = Y.all('body#page-course-view-topics span.commands a.editing_delete');
    
    list && list.each(function(node){ 
        var url = node.getAttribute('href');
        var delete_param = url.substring(url.lastIndexOf('&delete')); 
        node.setAttribute('href', M.cfg.wwwroot + '/local/dlelegacytools/confirmdelete/confirmmoddelete.php?sesskey=' + sessionkey + '&sr=1' + delete_param);
    });
}
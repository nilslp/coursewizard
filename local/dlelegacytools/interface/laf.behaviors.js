M.local_dlelegacytools_interface = {};

M.local_dlelegacytools_interface.init = function(Y,cfg){
    var el = {};
    
    if (cfg.courselink){
        el = Y.one('#region-main div.region-content');
        el && el.append( Y.Node.create(
            '<div class="buttons">'+cfg.courselink+'</div>'
        ));
    }
}
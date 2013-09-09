M.local_learningpool = {};

M.local_learningpool.init = function (Y) {    

    var treediv = {};
    var hierarchy = $hierarchy;
    var access = $accesslist;
    
    var tree = new YAHOO.widget.TreeView("hierarchy-tree");
    if (tree && hierarchy){
        treediv = Y.one('#hierarchy-tree')
        treediv.addClass('whitebg ygtv-checkbox');
        renderNodes(hierarchy.children, {}, tree.getRoot());
        tree.setNodesProperty('propagateHighlightUp',false);
        tree.setNodesProperty('propagateHighlightDown',true);
        tree.subscribe('clickEvent', onEventToggleHighlight);
        //tree.subscribe('clickEvent',tree.onEventToggleHighlight); 
        tree.render();
        treediv.append(Y.Node.create( '<input type="hidden" id="hierids" name="hierids" value="" />' ));
    }
    
    function onEventToggleHighlight( args ) {
        var ret = tree.onEventToggleHighlight( args );
        var input = Y.one('#hierids');
        var hierids = [];  
        var hid = '';   
        var i = 0;   
        var hiLit = tree.getNodesByProperty('highlightState',1);
        if (hiLit) {
            var labels = [];
            for (i = 0; i < hiLit.length; i++) {
                hid =  hiLit[i].data.id;
                hierids.push(hid.substr(hid.indexOf('_') + 1));
            }
        }
        input.set('value',hierids.join());
       /*if (args.node){
            var state = args.node.highlightState;
            var nid = args.node.getElId();
            var children = Y.all('#'+nid+' .ygtvitem');
            state ? children.addClass('parent-selected') : children.removeClass('parent-selected');
        }*/
        return ret;
    }
        
    function renderNodes( nodes, atts, parent ) {
        var k = '';
        var node = {};
        var idprefix = parent.data.id ? parent.data.id + '_' : 'hier_';
        for (k in nodes) {
            if (!nodes.hasOwnProperty(k)){
                continue;
            }
            
            node = new YAHOO.widget.TextNode({label:nodes[k].name,id:idprefix+k,highlightState:(nodes[k].access || 0)}, parent, true);
            node.collapse();
            renderNodes(nodes[k].children,{},node);
        }
    }
}

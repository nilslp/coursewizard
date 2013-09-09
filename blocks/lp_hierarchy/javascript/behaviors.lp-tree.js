// Learning Pool Tree View

M.block_lp_hierarchy = {};

M.block_lp_hierarchy.init = function(Y) {
	
	// Collapseable
	
	var branches = [];
	
	Y.all('ul.lp-tree li')
		.each(function (node) {
			if (node.one('ul')) {
				branches.push(node);
			}
		});
		
	Y.all(branches)
		.addClass('lp-tree-branch')
		.setAttribute('title', 'Click to expand')
		.on('click', 
			function(e) {
				if (e.target.one('ul')) e.target.toggleClass('lp-tree-expand');
				if (e.target.getAttribute('title') == 'Click to expand') {
					e.target.setAttribute('title','Click to collapse');
				}
				else {
					e.target.setAttribute('title','Click to expand');
				}
				e.stopPropagation();
			}
		);

	
}
M.block_lp_reportbuilder_scheduledonbehalf = {};

M.block_lp_reportbuilder_scheduledonbehalf.init = function(Y) {
    var clearButton = Y.one('#id_clearuser');
  
    clearButton.on('click', function(e) {
        var userFullName = Y.one('#id_userfullname');
        var userId = Y.one('#id_userid');
      
        userFullName.set('text', '');
        userId.set('value', '');
    });
};

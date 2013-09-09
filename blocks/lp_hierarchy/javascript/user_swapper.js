//for moving users from one list (representing a hierarchy) to another
M.block_lp_hierarchy_users = {};
M.block_lp_hierarchy_users.init = function (Y) {
    var sourceusers = Y.one('#id_source_users'),
        targetusers = Y.one('#id_target_users'),
        sourceusersarray,
        targetusersarray,
        mform = Y.one('#mform1'),
        sourceselect = Y.one('#sourceid'),
        targetselect = Y.one('#targetid'),
        ltor = Y.one('#id_lp_move_users_right'),
        rtol = Y.one('#id_lp_move_users_left'),
        addall = Y.one('#id_addall'),
        remall = Y.one('#id_removeall');
        source_users_search = Y.one('#id_search_source_users');
        target_users_search = Y.one('#id_search_target_users');

    //add search fields to the two select boxes
    // var source_users_search = add_search_div(sourceusers);
    // var target_users_search = add_search_div(targetusers);
    source_users_search && source_users_search.on('keyup', function () {
        filter_users(this, sourceusers);
    });
    target_users_search && target_users_search.on('keyup', function () {
        filter_users(this, targetusers);
    });

    // Adjust the height of the User Lists
    resizeLists();
    Y.one('window').on('resize', resizeLists);

    function resizeLists() {
        var select_height = Y.one('body').get('winHeight') * 0.6;
        sourceusers.setStyle('height', select_height+'px');
        targetusers.setStyle('height', select_height+'px');
    }

    // Hover event on user list
    sourceusers && sourceusers.delegate(
            'hover',
            function(e) { this.addClass('hover'); },
            function(e) { this.removeClass('hover'); },
            'option'
        );
    targetusers && targetusers.delegate(
            'hover',
            function(e) { this.addClass('hover'); },
            function(e) { this.removeClass('hover'); },
            'option'
        );

    //add listener to the button that moves selected users from left to right (ltor = l to r)
    ltor && ltor.on('click', function (e) {
        e.preventDefault();
        var all_options = Y.all('#id_source_users option');
        all_options.each(function (node) {
            if (node.get('selected')) {
                node.removeClass('selected').addClass('moved');
                targetusers.appendChild(node);
            }
        });
    });
    //add listener to the button that moves selected users from left to right (rtol = r to l)
    rtol && rtol.on('click', function (e) {
        e.preventDefault();
        var all_options = Y.all('#id_target_users option');
        all_options.each(function (node) {
            if (node.get('selected')) {
                node.removeClass('selected').addClass('moved');
                sourceusers.appendChild(node);
            }
        });
    });
    //add listener to the button that moves ALL from right to left
    remall && remall.on('click', function (e) {
        e.preventDefault();
        var ticked = Y.all('#id_target_users option')
        ticked.each(function (node) {
            node.removeClass('selected').addClass('moved');
            sourceusers.appendChild(node);
        });
    });
    //add listener to the button that moves ALL from left to right
    addall && addall.on('click', function (e) {
        e.preventDefault();
        var ticked = Y.all('#id_source_users option');
        ticked.each(function (node) {
            node.removeClass('selected').addClass('moved');
            targetusers.appendChild(node);
        });
    });
    //bypass normal "submit" when enter key is pressed
    mform.on("keypress", function(e) {
        if (e.keyCode == 13) {
            return false;
       }
    });

    //handle form submit
    mform.on('submit', function (e) {
        e.preventDefault();
        if (!has_usaved_edits()) {
            alert(M.util.get_string('nochanges', 'block_lp_hierarchy'));
            return;
        }
        else {
            save_changes(mform);
        }
    });
    //handle form reset
    mform.on('reset', function (e) {
        e.preventDefault();
        //we can reset the form by reloading the depts, which then resets the users
        var left_dept = sourceselect.get('value'),
            right_dept = targetselect.get('value');
        set_users(left_dept, sourceusers);
        set_users(right_dept, targetusers);
    });
    //############# METHODS FOR THE DELEGATES ################
    var on_click = function (e) {
        e.currentTarget.hasClass('selected') ? e.currentTarget.removeClass('selected') : e.currentTarget.addClass('selected');
    };
    var source_change = function (e) {
        var id = e.currentTarget.get('value');
        if (hierarchies_different()) {
            if (has_usaved_edits()) {
                if (confirm(M.util.get_string('discard', 'block_lp_hierarchy'))) {
                    set_users(id, sourceusers);
                }
            }
            else {

                set_users(id, sourceusers);
            }
        }
        ungrey_buttons();
    };
    var target_change = function (e) {
        var id = e.currentTarget.get('value');
        if (hierarchies_different()) {
            if (has_usaved_edits()) {
                if (confirm(M.util.get_string('discard', 'block_lp_hierarchy'))) {
                    set_users(id, targetusers);

                }
            }
            else {

                set_users(id, targetusers);

            }
        }
        ungrey_buttons();
    };
    mform.delegate('click', on_click, '.useroption');
    mform.delegate('change', source_change, '#sourceid');
    mform.delegate('change', target_change, '#targetid');

    //for IE we can't rely on bubbling up
    if (YAHOO.env.ua.ie > 0){
        sourceselect.on('change', function(e){
            source_change(e);
        });
        targetselect.on('change', function(e){
            target_change(e);
        });
    }
    function has_usaved_edits() {
        var moved = 'option.moved';
        return ((sourceusers && sourceusers.one(moved)) || (targetusers && targetusers.one(moved)));
    }

    function hierarchies_different() {
        if (targetselect.get('value') == sourceselect.get('value')) {
            alert(M.util.get_string('makechoice', 'block_lp_hierarchy'));
            return false;
        }
        else {
            return true;
        }
    }

    function ungrey_buttons(){
        if(( targetselect.get('value') != sourceselect.get('value') )  && sourceselect.get('value') > 0 && targetselect.get('value') > 0){
            Y.one("#id_lp_move_users_right").removeAttribute('disabled');
            Y.one("#id_addall").removeAttribute('disabled');
            Y.one("#id_removeall").removeAttribute('disabled');
            Y.one("#id_lp_move_users_left").removeAttribute('disabled');

        }else{
            Y.one("#id_lp_move_users_right").setAttribute('disabled', 'disabled');
            Y.one("#id_addall").setAttribute('disabled', 'disabled');
            Y.one("#id_removeall").setAttribute('disabled', 'disabled');
            Y.one("#id_lp_move_users_left").setAttribute('disabled', 'disabled');

        }
    }
    //function to set the users for the dept. in the userlist divs
    function set_users(dept, displaylist) {
        displaylist.get('childNodes').remove();
        Y.io('ajax/get_users_in_dept.php', {
            data: {
                'dept_id': dept
            },
            method: 'POST',
            on: {
                success: function (id, resp) {
                    var response = Y.JSON.parse(resp.responseText);
                    if (response.count_of_users) {
                        var users = response.users;
                        //stick the users into the arrays that we need to keep Chrome happy
                        if( displaylist.get('id') == 'id_target_users'){
                            targetusersarray = users;
                        }else{
                            sourceusersarray = users;
                        }
                        for (var i = 0; i < users.length; i++) {
                            var deleted_user = users[i].deleted === '1' ? ' deleted' : '';
                            displaylist.append('<option class="useroption' + deleted_user + '" id="user[' + users[i].id + ']">' + users[i].name + '</option>');
                        }
                        displaylist.set('multiple', 'multiple');
                    }
                },
                failure: function () {
                    //alert(M.util.get_string('nolist', 'block_lp_hierarchy'));
                }
            }
        });
    }

    function save_changes(form) {
        var left_dept = sourceselect.get('value'),
            right_dept = targetselect.get('value'),
            moved_to_left = get_moved_ids(sourceusers),
            moved_to_right = get_moved_ids(targetusers);
        Y.io('ajax/set_users_in_dept.php', {
            data: {
                'left_dept': left_dept,
                'right_dept': right_dept,
                'moved_to_left': moved_to_left,
                'moved_to_right': moved_to_right
            },
            method: 'POST',
            on: {
                success: function (id, resp) {
                    if (Y.JSON.parse(resp.responseText) && Y.JSON.parse(resp.responseText).success == true) {
                        //refresh the users in the lists and then they can carry on where they left off
                        set_users(left_dept, sourceusers);
                        set_users(right_dept, targetusers);
                        alert(M.util.get_string('lp_ajaxsuccess', 'block_lp_hierarchy'));
                    }
                    else {
                        // alert(M.util.get_string('lp_ajaxerror', 'block_lp_hierarchy'));
                    }
                },
                failure: function () {
                    // alert(M.util.get_string('lp_ajaxerror', 'block_lp_hierarchy'));
                }
            }
        });
    }

    function get_moved_ids(userlist) {
        var moved = userlist.all('option.moved');
        var ret = [];
        moved.each(function (node) {
            ret.push(node.get('id').replace(/[^0-9]/g, ''));
        });
        return Y.JSON.stringify(ret);
    }

    function filter_users(input, select) {
        var searchtext = input.get('value').toLowerCase(),
            matchingoption = -1,
            usersarray = select.get('id') == 'id_target_users' ? targetusersarray : sourceusersarray;
        //empty the select box for Chrome (well, for all browsers but we have to do it this way for Chrome)
        select.empty(true);
        Y.Array.each(usersarray, function (node) {
            var optiontext = node.name.toLowerCase();
            if (optiontext.indexOf(searchtext) >= 0) {
                var deleted_user = node.deleted === '1' ? ' deleted' : '';
                select.append('<option class="useroption' + deleted_user + '" id="user[' + node.id + ']">' + node.name + '</option>');

                if (matchingoption == -1) { //we found at least one
                    matchingoption = 1;
                }
            }
        });
        if (matchingoption == -1) { //the search didn't find any matching, color the search text in red
            input.addClass("error");
        }
        else {
            input.removeClass("error");
        }
    }

    function add_search_div(selectbox) {
        // Create a div to hold the search UI.
        var div = Y.Node.create('<div/>');
        div.set('id', 'searchdiv_' + selectbox.get('id'));
        div.addClass('searchdiv');
        var input = Y.Node.create('<input/>');
        input.set('type', 'text');
        input.set('id', 'search_' + selectbox.get('id'));
        var label = Y.Node.create('<label/>');
        label.set('for', input.get('id'));
        label.append(M.util.get_string('search', 'block_lp_hierarchy'));
        // Tie it all together
        div.append(label);
        div.append(input);
        selectbox.get('parentNode').append(div);
        return input;
    }
}

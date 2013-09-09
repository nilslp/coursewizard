M.block_shopping_basket_edit_product = {};

M.block_shopping_basket_edit_product.init = function (Y, delete_icon_url, selected_courses, selected_categories) {
    var add_button = Y.all('.add_button');
    var course_list = Y.one('#id_type');
    var category_list = Y.one('#id_categoryselect');
    var course_hidden_list = Y.one('input[name="courseidlist"]');
    var category_hidden_list = Y.one('input[name="categoryidlist"]');
    var course_container = Y.one('#course_list');
    var category_container = Y.one('#category_list');
    var courses_added = new Array();
    var categories_added = new Array();
    
    function setup() {
    
        switch_visibility();
    
        if (selected_courses.length != 0) {
            courses_added = selected_courses.split(',');
        }

        for (var i = 0; i < courses_added.length; i++) {
            disable_selected(true, courses_added[i]);
        }

        if (selected_categories.length != 0) {
            categories_added = selected_categories.split(',');
        }

        for (var i = 0; i < categories_added.length; i++) {
            disable_selected(false, categories_added[i]);
        }

        add_button.setAttribute('disabled', 'disabled');

        Y.all('.multiSelect').on('change', function(e) {
            var index = e.currentTarget.get('selectedIndex');
            var parent = e.currentTarget.ancestor();
            var button = parent.one('.add_button');

            if (index > 0) {
                button.removeAttribute('disabled');
            }
            else {
                button.setAttribute('disabled', 'disabled');
            }
        });

        add_button.on('click', function(e) {
            var course = e.currentTarget.get('id') == 'add_course';
            var list = course ? course_list : category_list;
            var hidden_list = course ? course_hidden_list : category_hidden_list;
            var index = list.get('selectedIndex');
            var items = hidden_list.get('value');

            if (index != 0) {
                var option = list.get("options").item(index);

                if (items.length == 0) {
                    create_table(course);
                }

                add_item(course, option.getAttribute('value'), option.get('text'));

                e.currentTarget.setAttribute('disabled', 'disabled');
                option.setAttribute('disabled');
            }
        });
        
        if(Y.one('#course_table') == null && course_hidden_list.get('value') != '') {
            populate_table(true);
        }
        
        if(Y.one('#category_table') == null && category_hidden_list.get('value') != '') {
            populate_table(false);
        }
        
        // Delegate the removal of courses
        course_container && course_container.delegate('click', remove_item, 'a');
        category_container && category_container.delegate('click', remove_item, 'a');
    }
    
    function create_table(course) {
        var prefix = course ? 'course_' : 'category_';
        var container = course ? course_container : category_container;
        container.set('innerHTML', '');

        // Create a new Node
        var table = Y.Node.create('<table id="' + prefix + 'table"><tbody id="' + prefix + 'data"></tbody></table>');

        container.appendChild(table);
    }
    
    function add_item(course, value, name) {
        var prefix = course ? 'course_' : 'category_';
        var table = course ? Y.one('#course_table > tbody') : Y.one('#category_table > tbody');
        var list = course ? course_hidden_list : category_hidden_list;
        var newRow = Y.Node.create('<tr id="' + prefix + 'row_' + value + '"><td>' + name + '</td><td><a href="#" id="' + prefix + value + '"><img src="' + delete_icon_url + '"></a></td></tr>');
        
        table.appendChild(newRow);
        
        var current_value = list.get('value');
        
        if (current_value.length != 0) {
            current_value = current_value + ',';
        }
        
        list.set('value', current_value + value);
        
    }

    function remove_item(e) {
        e.preventDefault();
        var course = e.currentTarget.get('id').indexOf('course_') != -1;
        var prefix = course ? 'course_' : 'category_';
        var replace = course ? 'course_' : 'category_';
        var this_item = e.currentTarget.get('id').replace(replace, '');
        var hidden_list = course ? course_hidden_list : category_hidden_list;
        var list = hidden_list.get('value').split(',');
        var new_list = new Array();
        
        for(var i = 0; i < list.length; i++){
            if (this_item != list[i]) {
                new_list.push(list[i]);
            }
        }
        
        hidden_list.set('value', new_list.join());
        
        var row = Y.one('#' + prefix + 'row_' + this_item);
        row && row.remove();
        
        enable_item(course,this_item);
    }
    
    function enable_item(course, item) {
        var list = course ? course_list : category_list;
        var index = list.get('selectedIndex');
        var parent = list.ancestor();
        var button = parent.one('input.add_button');
        
        if (index != 0) {
            if (list.get("options").item(index).get('value') == item) {
                button.removeAttribute('disabled');
            }
        }
        
        list.one('option[value="' + item + '"]').removeAttribute('disabled');
    }
    
    function disable_selected(course, item) {
        var list = course ? course_list : category_list;
        if(list) {
            var option = list.one('option[value="' + item + '"]');
            option && option.setAttribute('disabled', 'disabled');
        }
    }
    
    function switch_visibility() {
        var categoryRadios = Y.all('input[name="hascategory"]');
        var categoryGroup = Y.one('#fgroup_id_category_group');
        var courseGroup = Y.one('#fgroup_id_course_group');
        categoryRadios.on('change', function(e) {
            if(e.currentTarget.get('value') == '1') {
                categoryGroup.show();
                courseGroup.hide();
            } else {
                courseGroup.show();
                categoryGroup.hide();
            }
        });
        categoryRadios.each(function(node) {
            if(node.get('type') == 'radio') {
                if (node.get('checked') && node.get('value') == '1') {
                    categoryGroup.show();
                    courseGroup.hide();
                } else {
                    courseGroup.show();
                    categoryGroup.hide();
                }
            }
        });
    }
    
    function populate_table(course) {
        var prefix = course ? 'course_' : 'category_';
        var check_table = course ? Y.one('#course_table > tbody') : Y.one('#category_table > tbody');
        if(!check_table) {
            create_table(course);
        }
        var table = course ? Y.one('#course_table > tbody') : Y.one('#category_table > tbody');
        var select = course ? course_list : category_list;
        var list = course ? course_hidden_list.get('value').split(',') : category_hidden_list.get('value').split(',');
        
        if(list.length > 0) {
            for(var i = 0; i < list.length; i++){
                var option = select.one('option[value="' + list[i] + '"]');
                var newRow = Y.Node.create('<tr id="' + prefix + 'row_' + option.get('value') + '"><td>' + option.get('text') + '</td><td><a href="#" id="' + prefix + option.get('value') + '"><img src="' + delete_icon_url + '"></a></td></tr>');
                table.appendChild(newRow);
            }
        }
    }
    
    setup();
};
M.block_calendar_month = {};

M.block_calendar_month.init = function(Y) {

    function changeMonth(e) {
        e.preventDefault();
        var target = e.currentTarget;
        var href = target.getAttribute('href');
        Y.io('/blocks/calendar_month/index.php' + href.substr(href.indexOf('?')), {
            method: 'GET',
            on: {
                success: function(transactionId, o, args) {
                    var pn = target.get('parentNode').get('parentNode');
                    pn.setHTML(o.responseText);
                    var s = pn.getElementsByTagName('script');
                    eval(s.get('text'));
                }
            }
        });
    }
    
    function changeFilter(e) {
        e.preventDefault();
        var target = e.currentTarget;
        var link = target.one('a');
        var href = link.getAttribute('href');
        Y.io('/blocks/calendar_month/index.php' + href.substr(href.indexOf('?')), {
            method: 'GET',
            on: {
                success: function(transactionId, o, args) {
                    var pn = target.get('parentNode').get('parentNode').get('parentNode');
                    pn.setHTML(o.responseText);
                    var s = pn.getElementsByTagName('script');
                    eval(s.get('text'));
                }
            }
        });
    }

    var body = Y.one('body');

    if (body.hasClass('ie6') || body.hasClass('ie7')) {
        // Fall-back for older IE users
        var calendarItems = Y.all('.calendartable td');

        calendarItems.each(function(item) {
            if (item.hasClass('hasevent')) {
                // Set the title text
                var link = item.one('a');
                link.set('title', item.one('.events').get('text'));

                item.one('.events').remove();
            }
        });
    }

    var calendar_block = Y.one('.block_calendar_month');
    calendar_block && calendar_block.delegate('click', changeMonth, 'a.arrow_link');
    calendar_block && calendar_block.delegate('click', changeFilter, 'li.calendar_event');

}
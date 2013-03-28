/*
 * A global object for the admin
 */
AdminCore = {

    /**
     * Makes all ".action-confirm" link pop up with an "are you sure" message
     *
     * The title on the confirm is the "title" attribute of the element
     *
     * @param $wrapper
     */
    initializeConfirmAction: function($wrapper) {
        $wrapper.find('.action-confirm').on('click', function(e) {
            var msg = $(this).attr('title');
            if (!msg) {
                msg = 'Are you sure?';
            }

            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    }

};

jQuery(document).ready(function() {
    var $body = $('body');

    // setup .action-confirm behavior
    AdminCore.initializeConfirmAction($body);

    jQuery('table.tablesorter').tablesorter();

    jQuery('input.date-picker').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    jQuery('input.datetime-picker').datetimepicker({
        dateFormat: 'yy-mm-dd',
        showSecond: true
    });

    jQuery('.sidebar a[href="#"]').click(function() {
        alert('to be implemented');

        return false;
    });

    // make the textareas autogrow
    jQuery('textarea').autogrow();

    $('span.hour-selection option').each(function() {
        var hour    = parseInt($(this).text());

        if (isNaN(hour)) {
            return;
        }

        var newHour = hour;
        var amPm    = 'AM';

        if ((hour - 12) >= 0) {
            newHour = (hour > 12) ? (hour - 12) : hour;
            amPm    = 'PM';
        }

        if (hour == 0) {
            newHour = 12;
        }

        $(this).text($(this).text() + ' ('+ newHour + ' ' + amPm +')');
    });
});

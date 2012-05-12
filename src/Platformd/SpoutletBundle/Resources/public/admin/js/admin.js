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
});
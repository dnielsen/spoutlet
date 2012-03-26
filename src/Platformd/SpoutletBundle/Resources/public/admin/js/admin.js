jQuery(document).ready(function() {
    jQuery('table.tablesorter').tablesorter();

    jQuery('input.date-picker').datepicker({
        dateFormat: 'yy/mm/dd'
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
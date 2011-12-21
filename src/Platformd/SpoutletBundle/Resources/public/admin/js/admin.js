jQuery(document).ready(function() {
    jQuery('table.tablesorter').tablesorter();

    jQuery('input.date-picker').datepicker();

    jQuery('.sidebar a[href="#"]').click(function() {
        alert('to be implemented');

        return false;
    });
});
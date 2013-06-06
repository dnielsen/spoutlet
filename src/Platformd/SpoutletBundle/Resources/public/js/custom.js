/*
 * Core object we can begin to namespace functions and other objects inside of
 */
Core = {

    /**
     * Handles an event where a link is clicked, but we need to record that
     * click in google analytics before allowing the link to be followed
     *
     * @param e
     */
    _handleRecordableClick: function(href, category, action, label) {
        if (typeof _gat != 'undefined') {
            _gat._getTrackerByName()._trackEvent(category, action, label);
        }

        setTimeout('document.location = "' + href + '"', 100);
    }
};

/**
 * The site's main on ready block
 */
jQuery(document).ready(function() {
    /**
     * Handles tracking clicks as events in google analytics.
     * This requires the anchor to have the following:
     *
     *  * class = "ga-trackable"
     *  * data-category attribute (e.g. "ad")
     *  * data-action   attribute (e.g. "click")
     *  * data-label    attribute (e.g. "microsoft page link")
     */
    $('a.ga-trackable').live('click', function() {
        var href = $(this).attr('href');
        var category = $(this).data('category');
        var action = $(this).data('action');
        var label = $(this).data('label');

        Core._handleRecordableClick(href, category, action, label);

        return false;
    });

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

    // make background of a giveaway clickable
    $('#page').click(function(event) {
        if (event.target.id !== 'custom_background_bot') {
            return;
        }
        var link = $('#page').data('background-link');
        if (!link) {
            return;
        }

        if(link.indexOf("alienwarearena") !== -1) {
            window.location.href = link;
        } else {
            window.open(link, '_blank');
        }
    });
});

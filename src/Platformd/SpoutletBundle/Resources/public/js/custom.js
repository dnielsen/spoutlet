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
});

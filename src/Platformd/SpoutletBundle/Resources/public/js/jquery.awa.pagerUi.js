(function ($) {
    $.widget("awa.pagerUi", {
        options: {
            target: null
        },

        _create: function() {
            var self = this;

            self.target = self.options.target;

            self.element.find('a').each(function () {
                $(this).click(function () {
                    var button = $(this),
                        url = button.attr('href');

                    $.ajax({
                        url: url,
                        success: function(data) {
                            self._trigger("loaded", null, data);

                            self.target.html(data);
                        }
                    });

                    return false;
                });
            });
        }
    });
}(jQuery));
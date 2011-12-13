(function(){

    console.log('yay!');

    var setupProgressBar = function(){
        jQuery.fn.anim_progressbar = function (aOptions, totalSeconds) {
            // def values
            var iCms = 1000;

            // def options
            var aDefOpts = {
                start: new Date(), // now
                finish: new Date().setTime(new Date().getTime() + totalSeconds * iCms),
                interval: 100
            };
            var aOpts = jQuery.extend(aDefOpts, aOptions);
            var vPb = this;

            // each progress bar
            return this.each(
                function() {
                    var iDuration = aOpts.finish - aOpts.start;

                    // calling original progressbar
                    $(vPb).children('.pbar').progressbar();

                    // looping process
                    var vInterval = setInterval(
                        function(){
                            var iElapsedMs = new Date() - aOpts.start, // elapsed time in MS
                                iPerc = (iElapsedMs > 0) ? 100 - (iElapsedMs / iDuration * 100) : 0; // percentages

                            // display current positions and progress
                            $(vPb).children('.pbar').children('.ui-progressbar-value').css('width', iPerc+'%');

                            // in case of Finish
                            if (iPerc >= 100) {
                                clearInterval(vInterval);
                                $(vPb).children('.pbar').children('.ui-progressbar-value').css('width', 0);
                            }
                        } ,aOpts.interval
                    );
                }
            );
        }
    };


    // expose the function globally
    window.setupProgressBar = setupProgressBar;


}).call(this);
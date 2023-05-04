// Reload without displaying the cache when returning to the previous page with browser back.
(function( $ ) {
    "use strict";
    $(document).ready(function () {
        if (window.performance.navigation.type == 2) {
            window.location.reload();
        }
    });
})(jQuery);

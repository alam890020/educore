/**
 * MAKE SCHOOL - Admin Scripts
 * @package Make_School
 * @since 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize jQuery UI datepicker on date fields if available
        if ($.fn.datepicker) {
            $('.make-school-wrap input[type="date"]').each(function() {
                // Native date inputs are used, datepicker as fallback
                if (this.type !== 'date') {
                    $(this).datepicker({ dateFormat: 'yy-mm-dd' });
                }
            });
        }
    });

})(jQuery);

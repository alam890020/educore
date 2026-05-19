/* School Softwere - Admin JS */
(function ($) {
    'use strict';

    function showToast(message, type) {
        type = type || 'info';
        var bg = {
            success: 'linear-gradient(135deg,#10B981,#34D399)',
            warning: 'linear-gradient(135deg,#F59E0B,#FCD34D)',
            error:   'linear-gradient(135deg,#EF4444,#F87171)',
            info:    'linear-gradient(135deg,#4F46E5,#818CF8)'
        };
        if (typeof Toastify === 'undefined') {
            alert(message);
            return;
        }
        Toastify({
            text: message,
            duration: 3500,
            gravity: 'top',
            position: 'right',
            style: { background: bg[type] || bg.info, borderRadius: '10px' },
            stopOnFocus: true
        }).showToast();
    }

    window.ssToast = showToast;

    $(function () {
        // Sidebar toggle (mobile).
        $(document).on('click', '.ss-toggle-sidebar', function () {
            $('#ss-sidebar').toggleClass('open');
        });

        // DataTables.
        if ($.fn && $.fn.DataTable) {
            $('.ss-datatable').each(function () {
                $(this).DataTable({
                    pageLength: 20,
                    order: [],
                    language: { search: '', searchPlaceholder: 'Search...' }
                });
            });
        }

        // Select2.
        if ($.fn && $.fn.select2) {
            $('.ss-select2').select2({ width: '100%' });
        }

        // Flatpickr.
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.ss-date',     { dateFormat: 'Y-m-d' });
            flatpickr('.ss-datetime', { dateFormat: 'Y-m-d H:i', enableTime: true });
            flatpickr('.ss-time',     { dateFormat: 'H:i', enableTime: true, noCalendar: true });
        }

        // Photo preview.
        $(document).on('change', '.ss-photo-input', function () {
            var f = this.files && this.files[0];
            var $preview = $(this).closest('.ss-field').find('.ss-photo-preview');
            if (f && $preview.length) {
                var reader = new FileReader();
                reader.onload = function (e) { $preview.attr('src', e.target.result).show(); };
                reader.readAsDataURL(f);
            }
        });

        // Confirm-delete via SweetAlert2.
        $(document).on('click', '.ss-confirm-delete', function (e) {
            if (typeof Swal === 'undefined') { return true; }
            e.preventDefault();
            var url = $(this).attr('href');
            Swal.fire({
                title: SSAdmin.strings.confirm_delete,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(function (r) { if (r.isConfirmed) { window.location = url; } });
        });

        // Tabs.
        $(document).on('click', '.ss-tab', function () {
            var $tabs = $(this).closest('.ss-tabs');
            var target = $(this).data('tab');
            $tabs.find('.ss-tab').removeClass('active');
            $(this).addClass('active');
            var $container = $tabs.parent();
            $container.find('.ss-tab-pane').removeClass('active');
            $container.find('.ss-tab-pane[data-tab="' + target + '"]').addClass('active');
        });

        // Modal triggers.
        $(document).on('click', '[data-ss-modal]', function (e) {
            e.preventDefault();
            $('#' + $(this).data('ss-modal')).addClass('open');
        });
        $(document).on('click', '.ss-modal-bg, .ss-modal-close', function (e) {
            if (e.target === this || $(this).hasClass('ss-modal-close')) {
                $(this).closest('.ss-modal-bg').removeClass('open');
            }
        });

        // Auto-show flash notice (?ss_notice=...) handled by PHP.

        // Form submit spinner.
        $(document).on('submit', '.ss-form', function () {
            var $btn = $(this).find('button[type=submit]');
            $btn.prop('disabled', true).prepend('<i class="ph ph-spinner ss-spin"></i> ');
        });
    });
})(jQuery);

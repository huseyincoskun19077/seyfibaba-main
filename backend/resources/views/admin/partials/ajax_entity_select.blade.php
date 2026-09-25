{{-- Select2: ürün/satıcı AJAX arama + yerel kategori dropdown --}}
<script>
(function ($) {
    "use strict";
    $(function () {
        // footer select2.min.js + $('.select2').select2() sonrasında çalış
        setTimeout(function () {
            var productUrl = @json($productUrl ?? route('admin.lookup.products'));
            var vendorUrl = @json($vendorUrl ?? null);

            function initAjaxSelect($el, url) {
                if (!$el.length || !url || typeof $.fn.select2 !== 'function') return;
                $el.each(function () {
                    var $one = $(this);
                    if ($one.hasClass('select2-hidden-accessible')) {
                        $one.select2('destroy');
                    }
                    $one.select2({
                        width: '100%',
                        placeholder: $one.data('placeholder') || 'Ara ve seç…',
                        allowClear: true,
                        minimumInputLength: 0,
                        ajax: {
                            url: url,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { q: params.term || '' };
                            },
                            processResults: function (data) {
                                return { results: (data && data.results) ? data.results : [] };
                            },
                            cache: true
                        }
                    });
                });
            }

            function initLocalSelect($el) {
                if (!$el.length || typeof $.fn.select2 !== 'function') return;
                $el.each(function () {
                    var $one = $(this);
                    if ($one.hasClass('select2-hidden-accessible')) {
                        $one.select2('destroy');
                    }
                    $one.select2({
                        width: '100%',
                        placeholder: $one.data('placeholder') || 'Ara ve seç…',
                        allowClear: true
                    });
                });
            }

            initAjaxSelect($('.js-ajax-products'), productUrl);
            if (vendorUrl) {
                initAjaxSelect($('.js-ajax-vendors'), vendorUrl);
            }
            initLocalSelect($('.js-local-select'));
        }, 120);
    });
})(jQuery);
</script>

{{-- AI Content Generator Modal — Ürün + Blog uyumlu --}}
@if (isset($aiEnabled) && $aiEnabled)
<div class="modal fade" id="aiContentModal" tabindex="-1" role="dialog" aria-labelledby="aiContentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="aiContentModalLabel">
                    <i class="fas fa-robot mr-2"></i> Yapay zeka ile içerik üret
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="ai-modal-description"></p>

                <div class="d-flex flex-column" style="gap: 10px;">
                    <button type="button" class="btn btn-primary btn-block ai-action-btn" data-action="full">
                        <i class="fas fa-magic mr-1"></i> Tam içerik üret
                        <small class="d-block font-weight-normal opacity-75">Başlık, kısa/uzun açıklama ve SEO</small>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-block ai-action-btn" data-action="enhance">
                        <i class="fas fa-arrow-up mr-1"></i> Mevcut içeriği iyileştir
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-block ai-action-btn" data-action="generate_meta">
                        <i class="fas fa-search mr-1"></i> Sadece SEO üret
                        <small class="d-block font-weight-normal text-muted">Başlık + açıklamaya göre meta</small>
                    </button>
                </div>

                <div class="d-none mt-3" id="ai-loading">
                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                        <span>Yapay zeka içerik üretiyor, lütfen bekleyin... (10-30 saniye)</span>
                    </div>
                </div>

                <div class="d-none mt-3" id="ai-error">
                    <div class="alert alert-danger mb-0" id="ai-error-message"></div>
                </div>

                <div class="d-none mt-3" id="ai-success">
                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                        <span><i class="fas fa-check-circle mr-1"></i> İçerik başarıyla üretildi!</span>
                        <button type="button" class="btn btn-sm btn-outline-success" id="ai-undo-btn">
                            <i class="fas fa-undo mr-1"></i> Geri Al
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    "use strict";

    var aiPreviousValues = {};

    function detectContentType() {
        if ($('input[name="title"]').length > 0 && $('input[name="name"]').length === 0) {
            return 'blog';
        }
        return 'product';
    }

    function seoField(name) {
        return $('input[name="' + name + '"], textarea[name="' + name + '"]').first();
    }

    function getFormValues() {
        var type = detectContentType();
        if (type === 'blog') {
            return {
                title: $('input[name="title"]').val(),
                description: $('.summernote').summernote ? $('.summernote').summernote('code') : $('textarea[name="description"]').val(),
                seo_title: seoField('seo_title').val(),
                seo_description: seoField('seo_description').val(),
            };
        }
        return {
            name: $('input[name="name"]').val(),
            short_name: $('input[name="short_name"]').val(),
            short_description: $('textarea[name="short_description"]').val(),
            long_description: $('.summernote').summernote ? $('.summernote').summernote('code') : $('textarea[name="long_description"]').val(),
            seo_title: seoField('seo_title').val(),
            seo_description: seoField('seo_description').val(),
            tags: $('input[name="tags"]').val() || '',
        };
    }

    function setFormValues(data) {
        var type = detectContentType();

        if (type === 'blog') {
            if (data.title) {
                $('input[name="title"]').val(data.title);
                $('#slug').val(convertToSlug(data.title));
            }
            if (data.description) {
                if ($('.summernote').summernote) {
                    $('.summernote').summernote('code', data.description);
                } else {
                    $('textarea[name="description"]').val(data.description);
                }
            }
        } else {
            if (data.name) {
                $('input[name="name"]').val(data.name);
                $('input[name="short_name"]').val(data.name.substring(0, 80));
                $('#slug').val(convertToSlug(data.name));
            }
            if (data.short_description) {
                $('textarea[name="short_description"]').val(data.short_description);
            } else if (data.long_description) {
                var plain = $('<div>').html(data.long_description).text().trim();
                if (plain) {
                    $('textarea[name="short_description"]').val(plain);
                    data.short_description = plain;
                }
            }
            // Tek açıklama: kısa = detaylı
            var shortVal = $('textarea[name="short_description"]').val() || '';
            if (shortVal) {
                var html = '<p>' + $('<div>').text(shortVal).html().replace(/\n/g, '<br>') + '</p>';
                var $long = $('input[name="long_description"], textarea[name="long_description"]').first();
                $long.val(html);
                if ($('.summernote').summernote && $('textarea[name="long_description"]').hasClass('summernote')) {
                    $('.summernote').summernote('code', html);
                }
            } else if (data.long_description) {
                if ($('.summernote').summernote) {
                    $('.summernote').summernote('code', data.long_description);
                } else {
                    $('textarea[name="long_description"], input[name="long_description"]').val(data.long_description);
                }
            }
            if (data.tags && $('input[name="tags"]').length) {
                $('input[name="tags"]').val(data.tags);
            }
        }

        if (data.seo_title) {
            seoField('seo_title').val(data.seo_title);
        }
        if (data.seo_description) {
            seoField('seo_description').val(data.seo_description);
        }
    }

    $('#aiContentModal').on('show.bs.modal', function () {
        var type = detectContentType();
        if (type === 'blog') {
            $('#ai-modal-description').text('Blog başlığını girin. Yapay zeka içerik ve SEO üretir.');
        } else {
            $('#ai-modal-description').text('Önce ürün adını yazın. İçerik Kuaför Tedarik’e (berber, kuaför, salon malzemeleri) göre üretilir; SEO başlık ve açıklamadan otomatik dolar.');
        }
    });

    $(document).on('click', '.ai-action-btn', function(e) {
        e.preventDefault();

        var type = detectContentType();
        var action = $(this).data('action');
        var contentName = type === 'blog'
            ? $('input[name="title"]').val()
            : ($('input[name="name"]').val() || $('input[name="short_name"]').val());
        var categoryName = $('#category option:selected').text();
        var selectPlaceholder = @json(__('admin.Select Category'));

        if (!contentName || !String(contentName).trim()) {
            var label = type === 'blog' ? 'blog başlığını' : 'ürün adını';
            toastr.warning('Lütfen en az ' + label + ' girin.');
            $('#aiContentModal').modal('hide');
            if (type === 'blog') {
                $('input[name="title"]').focus();
            } else {
                $('input[name="name"]').focus();
            }
            return;
        }

        if (action === 'generate_meta' || action === 'enhance') {
            var shortDesc = $('textarea[name="short_description"]').val() || '';
            if (type === 'product' && action === 'generate_meta' && !String(shortDesc).trim()) {
                toastr.warning('SEO için kısa açıklama da yazın veya önce “Tam içerik üret” kullanın.');
            }
        }

        aiPreviousValues = getFormValues();

        $('#ai-loading').removeClass('d-none');
        $('#ai-error').addClass('d-none');
        $('#ai-success').addClass('d-none');
        $('.ai-action-btn').prop('disabled', true);

        var existingContent = {};
        if (action !== 'full') {
            existingContent = getFormValues();
        } else {
            existingContent = { name: contentName };
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('seller.ai-generate-content') }}",
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                content_type: type,
                product_name: contentName,
                category_name: (categoryName && categoryName !== selectPlaceholder) ? categoryName : '',
                existing_content: existingContent,
            },
            timeout: 120000,
            success: function(response) {
                $('#ai-loading').addClass('d-none');
                $('.ai-action-btn').prop('disabled', false);

                if (response.success && response.results) {
                    setFormValues(response.results);
                    $('#ai-success').removeClass('d-none');
                    toastr.success('İçerik başarıyla üretildi!');
                    setTimeout(function() { $('#aiContentModal').modal('hide'); }, 1200);
                } else {
                    $('#ai-error-message').text(response.message || 'Bilinmeyen hata.');
                    $('#ai-error').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#ai-loading').addClass('d-none');
                $('.ai-action-btn').prop('disabled', false);
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'İstek hatası.';
                $('#ai-error-message').text(msg);
                $('#ai-error').removeClass('d-none');
                toastr.error(msg);
            }
        });
    });

    $(document).on('click', '#ai-undo-btn', function() {
        if (aiPreviousValues) {
            setFormValues(aiPreviousValues);
            $('#ai-success').addClass('d-none');
            $('#aiContentModal').modal('hide');
            toastr.info('Önceki içerik geri yüklendi.');
        }
    });

})(jQuery);
</script>
@endif

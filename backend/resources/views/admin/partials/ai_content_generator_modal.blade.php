{{-- AI Content Generator Modal — Ürün + Blog uyumlu --}}
@if (isset($aiEnabled) && $aiEnabled)
<div class="modal fade" id="aiContentModal" tabindex="-1" role="dialog" aria-labelledby="aiContentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="aiContentModalLabel">
                    <i class="fas fa-robot mr-2"></i> Yapay Zeka ile Otomatik İçerik Üret
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="ai-modal-description"></p>

                <div class="d-flex flex-column" style="gap: 10px;">
                    <button type="button" class="btn btn-primary btn-block ai-action-btn" data-action="full">
                        <i class="fas fa-magic mr-1"></i> Tam İçerik Üret
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-block ai-action-btn" data-action="enhance">
                        <i class="fas fa-arrow-up mr-1"></i> Mevcut İçeriği İyileştir
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-block ai-action-btn" data-action="generate_meta">
                        <i class="fas fa-search mr-1"></i> Sadece SEO Üret
                    </button>
                </div>

                <div class="d-none mt-3" id="ai-loading">
                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                        <span>Yapay zeka içerik üretiyor, lütfen bekleyin... (10-30 saniye sürer)</span>
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

    // Sayfa tipini otomatik algıla
    function detectContentType() {
        // Blog sayfasında "title" input'u var, "name" yok
        if ($('input[name="title"]').length > 0 && $('input[name="name"]').length === 0) {
            return 'blog';
        }
        return 'product';
    }

    function getFormValues() {
        var type = detectContentType();
        if (type === 'blog') {
            return {
                title: $('input[name="title"]').val(),
                description: $('.summernote').summernote ? $('.summernote').summernote('code') : $('textarea[name="description"]').val(),
                seo_title: $('input[name="seo_title"]').val(),
                seo_description: $('textarea[name="seo_description"]').val(),
            };
        }
        return {
            name: $('input[name="name"]').val(),
            short_name: $('input[name="short_name"]').val(),
            short_description: $('textarea[name="short_description"]').val(),
            long_description: $('.summernote').summernote ? $('.summernote').summernote('code') : $('textarea[name="long_description"]').val(),
            seo_title: $('input[name="seo_title"]').val(),
            seo_description: $('textarea[name="seo_description"]').val(),
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
            }
            if (data.long_description) {
                if ($('.summernote').summernote) {
                    $('.summernote').summernote('code', data.long_description);
                } else {
                    $('textarea[name="long_description"]').val(data.long_description);
                }
            }
        }

        if (data.seo_title) {
            $('input[name="seo_title"]').val(data.seo_title);
        }
        if (data.seo_description) {
            $('textarea[name="seo_description"]').val(data.seo_description);
        }
    }

    // Modal açılınca açıklama metnini güncelle
    $('#aiContentModal').on('show.bs.modal', function () {
        var type = detectContentType();
        if (type === 'blog') {
            $('#ai-modal-description').text('Blog başlığını girin ve aşağıdaki butonlardan birini seçin. Yapay zeka otomatik olarak blog içeriği ve SEO etiketleri oluşturur.');
        } else {
            $('#ai-modal-description').text('Ürün adını girin ve aşağıdaki butonlardan birini seçin. Yapay zeka otomatik olarak ürün içeriği, SEO etiketleri ve açıklamaları oluşturur.');
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

        if (!contentName) {
            var label = type === 'blog' ? 'blog başlığını' : 'ürün adını';
            toastr.warning('Lütfen en az ' + label + ' girin.');
            $('#aiContentModal').modal('hide');
            if (type === 'blog') {
                $('input[name="title"]').focus();
            } else {
                $('input[name="short_name"]').focus();
            }
            return;
        }

        aiPreviousValues = getFormValues();

        $('#ai-loading').removeClass('d-none');
        $('#ai-error').addClass('d-none');
        $('#ai-success').addClass('d-none');
        $('.ai-action-btn').prop('disabled', true);

        var existingContent = {};
        if (action !== 'full') {
            existingContent = getFormValues();
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('admin.ai-generate-content') }}",
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                content_type: type,
                product_name: contentName,
                category_name: categoryName,
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
                    setTimeout(function() { $('#aiContentModal').modal('hide'); }, 1500);
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

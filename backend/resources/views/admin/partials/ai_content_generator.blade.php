{{-- AI Content Generator Panel (Admin) --}}
@if (isset($aiEnabled) && $aiEnabled)
<div class="card mt-3 mb-3 border-primary" id="ai-content-card">
    <div class="card-header bg-light">
        <h4 class="d-flex align-items-center mb-0">
            <i class="fas fa-robot text-primary mr-2"></i>
            AI ile Otomatik Icerik Uret
            <span class="badge badge-success ml-2" style="font-size:11px;">Beta</span>
        </h4>
        <div class="card-header-action">
            <a data-collapse="#ai-collapse" class="btn btn-icon btn-info" href="#"><i class="fas fa-chevron-down"></i></a>
        </div>
    </div>
    <div class="collapse show" id="ai-collapse">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <p class="text-muted mb-3">
                        Urun adini girin ve asagidaki butonlardan birini secin. AI otomatik olarak urun icerigi, SEO etiketleri ve aciklamalari olusturur.
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary ai-action-btn" data-action="full" id="ai-btn-full">
                            <i class="fas fa-magic mr-1"></i> Tam Icerik Uret
                        </button>
                        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="sr-only">Diger</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item ai-action-btn" href="#" data-action="enhance">
                                <i class="fas fa-arrow-up mr-1"></i> Mevcut Icerigi Iyilestir
                            </a>
                            <a class="dropdown-item ai-action-btn" href="#" data-action="generate_meta">
                                <i class="fas fa-search mr-1"></i> Sadece SEO Uret
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none" id="ai-loading">
                <div class="alert alert-info d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                    <span>AI icerik uretiyor, lutfen bekleyin... (10-30 saniye surer)</span>
                </div>
            </div>

            <div class="d-none" id="ai-error">
                <div class="alert alert-danger" id="ai-error-message"></div>
            </div>

            <div class="d-none" id="ai-success">
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle mr-1"></i> Icerik basariyla uretildi!</span>
                    <button type="button" class="btn btn-sm btn-outline-success" id="ai-undo-btn">
                        <i class="fas fa-undo mr-1"></i> Geri Al
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    "use strict";

    var aiPreviousValues = {};

    function getFormValues() {
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
        if (data.seo_title) {
            $('input[name="seo_title"]').val(data.seo_title);
        }
        if (data.seo_description) {
            $('textarea[name="seo_description"]').val(data.seo_description);
        }
    }


    $(document).on('click', '.ai-action-btn', function(e) {
        e.preventDefault();

        var action = $(this).data('action');
        var lang = $(this).data('lang') || 'en';
        var productName = $('input[name="name"]').val() || $('input[name="short_name"]').val();
        var categoryName = $('#category option:selected').text();

        if (!productName) {
            toastr.warning('Lutfen en az urun adini girin.');
            $('input[name="short_name"]').focus();
            return;
        }

        aiPreviousValues = getFormValues();

        $('#ai-loading').removeClass('d-none');
        $('#ai-error').addClass('d-none');
        $('#ai-success').addClass('d-none');
        $('.ai-action-btn').prop('disabled', true);

        var existingContent = {};
        if (action !== 'full') {
            existingContent = {
                name: $('input[name="name"]').val(),
                short_description: $('textarea[name="short_description"]').val(),
                long_description: $('.summernote').summernote ? $('.summernote').summernote('code') : $('textarea[name="long_description"]').val(),
                seo_title: $('input[name="seo_title"]').val(),
                seo_description: $('textarea[name="seo_description"]').val(),
            };
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('admin.ai-generate-content') }}",
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                product_name: productName,
                category_name: categoryName,
                existing_content: existingContent,
                target_lang: lang,
            },
            timeout: 120000,
            success: function(response) {
                $('#ai-loading').addClass('d-none');
                $('.ai-action-btn').prop('disabled', false);

                if (response.success && response.results) {
                    setFormValues(response.results);
                    $('#ai-success').removeClass('d-none');
                    toastr.success('AI icerigi basariyla uretildi!');
                } else {
                    $('#ai-error-message').text(response.message || 'Bilinmeyen hata.');
                    $('#ai-error').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#ai-loading').addClass('d-none');
                $('.ai-action-btn').prop('disabled', false);
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'AI istek hatasi.';
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
            toastr.info('Onceki icerik geri yuklendi.');
        }
    });

})(jQuery);
</script>
@endif

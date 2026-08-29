@extends('admin.master_layout')
@section('title')
<title>AI Ayarları - {{ __('admin.Admin Panel') }}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>AI İçerik Üretimi Ayarları</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">AI Ayarları</div>
            </div>
          </div>

          <div class="section-body">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-info-circle mr-2"></i>Bilgilendirme</h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">AI içerik üretimi, satıcıların ürün eklerken/güncellerken yapay zeka ile otomatik içerik oluşturmasını sağlar. En az bir sağlayıcıyı aktifleştirip API anahtarını girmeniz gerekir.</p>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.update-ai-settings') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- OpenAI / Groq Card --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-brain mr-2"></i>OpenAI / Groq</h4>
                                <div class="card-header-action">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="openai_enabled" name="openai_enabled" value="1" {{ $setting->openai_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="openai_enabled">Aktif</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>API Anahtarı</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="openai_api_key" id="openai_api_key" value="{{ $setting->openai_api_key ? substr($setting->openai_api_key, 0, 4) . '****' . substr($setting->openai_api_key, -4) : '' }}" placeholder="sk-... veya gsk_...">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="openai_api_key"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <small class="text-muted">OpenAI: <code>sk-</code> ile başlar | Groq: <code>gsk_</code> ile başlar (otomatik algılanır)</small>
                                </div>

                                <div class="form-group">
                                    <label>Model</label>
                                    <select class="form-control" name="openai_model">
                                        <optgroup label="OpenAI">
                                            <option value="gpt-4o-mini" {{ ($setting->openai_model ?? 'gpt-4o-mini') == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Hızlı, Ekonomik)</option>
                                            <option value="gpt-4o" {{ ($setting->openai_model ?? '') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Yüksek Kalite)</option>
                                            <option value="gpt-4.1-mini" {{ ($setting->openai_model ?? '') == 'gpt-4.1-mini' ? 'selected' : '' }}>GPT-4.1 Mini</option>
                                            <option value="gpt-4.1" {{ ($setting->openai_model ?? '') == 'gpt-4.1' ? 'selected' : '' }}>GPT-4.1</option>
                                        </optgroup>
                                        <optgroup label="Groq (gsk_ anahtarı gerektirir)">
                                            <option value="llama-3.3-70b-versatile" {{ ($setting->openai_model ?? '') == 'llama-3.3-70b-versatile' ? 'selected' : '' }}>Llama 3.3 70B (Ücretsiz, Hızlı)</option>
                                            <option value="llama-3.1-8b-instant" {{ ($setting->openai_model ?? '') == 'llama-3.1-8b-instant' ? 'selected' : '' }}>Llama 3.1 8B Instant</option>
                                            <option value="mixtral-8x7b-32768" {{ ($setting->openai_model ?? '') == 'mixtral-8x7b-32768' ? 'selected' : '' }}>Mixtral 8x7B</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Timeout (saniye)</label>
                                    <input type="number" class="form-control" name="openai_timeout" value="{{ $setting->openai_timeout ?? 60 }}" min="10" max="300">
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-info test-connection" data-provider="openai">
                                    <i class="fas fa-plug mr-1"></i>Bağlantıyı Test Et
                                </button>
                                <span class="ml-2 test-result" id="openai-test-result"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Claude Card --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-robot mr-2"></i>Claude (Anthropic)</h4>
                                <div class="card-header-action">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="claude_enabled" name="claude_enabled" value="1" {{ $setting->claude_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="claude_enabled">Aktif</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>API Anahtarı</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="claude_api_key" id="claude_api_key" value="{{ $setting->claude_api_key ? substr($setting->claude_api_key, 0, 4) . '****' . substr($setting->claude_api_key, -4) : '' }}" placeholder="sk-ant-...">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="claude_api_key"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <small class="text-muted"><a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a> adresinden alınır</small>
                                </div>

                                <div class="form-group">
                                    <label>Model</label>
                                    <select class="form-control" name="claude_model">
                                        <option value="claude-sonnet-4-5-20250929" {{ ($setting->claude_model ?? 'claude-sonnet-4-5-20250929') == 'claude-sonnet-4-5-20250929' ? 'selected' : '' }}>Claude Sonnet 4.5 (Dengeli)</option>
                                        <option value="claude-haiku-4-5-20251001" {{ ($setting->claude_model ?? '') == 'claude-haiku-4-5-20251001' ? 'selected' : '' }}>Claude Haiku 4.5 (Hızlı, Ekonomik)</option>
                                        <option value="claude-opus-4-6" {{ ($setting->claude_model ?? '') == 'claude-opus-4-6' ? 'selected' : '' }}>Claude Opus 4.6 (En Güçlü)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Timeout (saniye)</label>
                                    <input type="number" class="form-control" name="claude_timeout" value="{{ $setting->claude_timeout ?? 60 }}" min="10" max="300">
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-info test-connection" data-provider="claude">
                                    <i class="fas fa-plug mr-1"></i>Bağlantıyı Test Et
                                </button>
                                <span class="ml-2 test-result" id="claude-test-result"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save mr-1"></i>Ayarları Kaydet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Usage Info --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-question-circle mr-2"></i>Nasıl Çalışır?</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Desteklenen İşlemler</h6>
                                    <ul>
                                        <li><strong>Tam İçerik Üretimi:</strong> Ürün adı, açıklama, SEO meta, etiketler, iade/teslimat metni</li>
                                        <li><strong>İçerik İyileştirme:</strong> Mevcut ürün içeriğini SEO uyumlu hale getirir</li>
                                        <li><strong>Çeviri:</strong> Ürün içeriğini farklı dillere çevirir</li>
                                        <li><strong>Meta Üretimi:</strong> Mevcut içerikten SEO meta etiketleri oluşturur</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>API Anahtarı Alma</h6>
                                    <ul>
                                        <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></li>
                                        <li><strong>Groq (Ücretsiz):</strong> <a href="https://console.groq.com/keys" target="_blank">console.groq.com/keys</a> — <code>gsk_</code> ile başlayan anahtar</li>
                                        <li><strong>Claude:</strong> <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a></li>
                                    </ul>
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="fas fa-lightbulb mr-1"></i> İki sağlayıcı da aktifse, önce OpenAI/Groq denenir, başarısız olursa Claude'a geçilir (fallback).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </section>
      </div>

<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            var target = $(this).data('target');
            var input = $('#' + target);
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Test connection
        $('.test-connection').on('click', function() {
            var provider = $(this).data('provider');
            var resultEl = $('#' + provider + '-test-result');
            var btn = $(this);

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Test ediliyor...');
            resultEl.html('');

            $.ajax({
                type: 'POST',
                url: "{{ route('admin.test-ai-connection') }}",
                data: {
                    _token: '{{ csrf_token() }}',
                    provider: provider
                },
                success: function(response) {
                    if (response.success) {
                        resultEl.html('<span class="text-success"><i class="fas fa-check-circle"></i> ' + response.message + '</span>');
                    } else {
                        resultEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + response.message + '</span>');
                    }
                },
                error: function() {
                    resultEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> Bağlantı hatası</span>');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-plug mr-1"></i>Bağlantıyı Test Et');
                }
            });
        });

        // Clear masked value on focus
        $('input[name="openai_api_key"], input[name="claude_api_key"]').on('focus', function() {
            if ($(this).val().includes('****')) {
                $(this).val('');
            }
        });
    });
</script>
@endsection

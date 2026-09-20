@extends('admin.master_layout')
@section('title')
<title>Reklam Asistanı - {{ __('admin.Admin Panel') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1><i class="fas fa-bullhorn mr-2"></i>Reklam Asistanı</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Reklam Asistanı</div>
      </div>
    </div>

    <div class="section-body">
      <div class="alert alert-info">
        <strong>Kapsam:</strong> Yalnızca Seyfibaba reklam / pazarlama. Sipariş, müşteri, ödeme veya güvenlik yok.
        Asistan projeyi değiştiremez; bilgi bankası + sohbet ile reklam metni ve görsel üretir.
        Model: <code>{{ $chatModel }}</code> (yoksa yedek) · Görsel: <code>{{ $imageModel }}</code>
      </div>

      @if(!$openaiReady)
        <div class="alert alert-warning">
          OpenAI anahtarı gerekli (Groq değil).
          <a href="{{ route('admin.ai-settings') }}">AI Ayarları</a> sayfasından OpenAI’yi açıp <code>sk-</code> anahtarı girin.
        </div>
      @endif

      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="mb-0"><i class="fas fa-comments mr-2"></i>Sohbet</h4>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="ad-clear-chat">Sohbeti temizle</button>
            </div>
            <div class="card-body p-0">
              <div id="ad-chat-log" class="p-3" style="height: 420px; overflow-y: auto; background: #f8f9fa;">
                @forelse($history as $row)
                  <div class="mb-3 {{ ($row['role'] ?? '') === 'user' ? 'text-right' : '' }}">
                    <div class="d-inline-block text-left p-2 rounded {{ ($row['role'] ?? '') === 'user' ? 'bg-primary text-white' : 'bg-white border' }}" style="max-width: 90%; white-space: pre-wrap;">{{ $row['content'] ?? '' }}</div>
                  </div>
                @empty
                  <p class="text-muted mb-0" id="ad-chat-empty">Örnek: “Salon sahiplerine Instagram feed reklamı yaz” veya “Satıcı kazanımı için Meta kampanya metni üret”.</p>
                @endforelse
              </div>
              <div class="p-3 border-top">
                <div class="form-group mb-2">
                  <textarea id="ad-chat-input" class="form-control" rows="3" placeholder="Reklam hakkında sor veya metin iste..." @disabled(!$openaiReady)></textarea>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-primary" id="ad-chat-send" @disabled(!$openaiReady)>
                    <i class="fas fa-paper-plane mr-1"></i> Gönder
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm ad-quick" data-msg="Alıcı (salon) için Instagram feed reklam metni yaz: kuaför koltuğu / malzeme. CTA: seyfibaba.com">Alıcı reklamı</button>
                  <button type="button" class="btn btn-outline-primary btn-sm ad-quick" data-msg="Satıcı kazanımı için Meta reklam metni yaz. %10 komisyon, abonelik yok, /satici-kayit. CTA ve hedef kitle öner.">Satıcı reklamı</button>
                  <button type="button" class="btn btn-outline-primary btn-sm ad-quick" data-msg="Google Ads için 5 kısa başlık + 2 açıklama öner (alıcı odaklı, Seyfibaba).">Google Ads</button>
                </div>
                <small class="text-muted d-block mt-2" id="ad-chat-status"></small>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-image mr-2"></i>Görsel üret</h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label>Görsel açıklaması (prompt)</label>
                <textarea id="ad-image-prompt" class="form-control" rows="3" placeholder="Örn: Modern Turkish barbershop interior, professional salon chair, warm light, clean marketplace ad style, no text" @disabled(!$openaiReady)></textarea>
              </div>
              <div class="form-group">
                <label>Boyut</label>
                <select id="ad-image-size" class="form-control" style="max-width: 240px;" @disabled(!$openaiReady)>
                  <option value="1024x1024">Kare 1024 (feed)</option>
                  <option value="1024x1536">Dikey (story)</option>
                  <option value="1536x1024">Yatay</option>
                </select>
              </div>
              <button type="button" class="btn btn-success" id="ad-image-gen" @disabled(!$openaiReady)>
                <i class="fas fa-magic mr-1"></i> Görsel üret
              </button>
              <small class="text-muted d-block mt-2" id="ad-image-status"></small>
              <div id="ad-image-result" class="mt-3" style="display:none;">
                <img id="ad-image-preview" src="" alt="Üretilen reklam görseli" class="img-fluid rounded border" style="max-height: 420px;">
                <div class="mt-2">
                  <a id="ad-image-link" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">Tam boyutta aç / indir</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><h4>Proje bilgi bankası</h4></div>
            <div class="card-body" style="max-height: 640px; overflow-y: auto; font-size: 13px;">
              <p class="text-muted">Asistan yalnızca bunlara göre konuşur. Sipariş/ödeme yok.</p>
              @foreach($knowledge as $key => $value)
                <div class="mb-3">
                  <strong class="text-uppercase">{{ str_replace('_', ' ', $key) }}</strong>
                  <div class="text-muted mt-1">
                    @if(is_array($value))
                      <ul class="pl-3 mb-0">
                        @foreach($value as $item)
                          <li>{{ $item }}</li>
                        @endforeach
                      </ul>
                    @else
                      {{ $value }}
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@section('script')
<script>
(function () {
  var csrf = @json(csrf_token());
  var chatUrl = @json(route('admin.ad-assistant.chat'));
  var imageUrl = @json(route('admin.ad-assistant.image'));
  var clearUrl = @json(route('admin.ad-assistant.clear'));

  function appendBubble(role, text) {
    $('#ad-chat-empty').remove();
    var isUser = role === 'user';
    var wrap = $('<div class="mb-3"></div>').toggleClass('text-right', isUser);
    var bubble = $('<div class="d-inline-block text-left p-2 rounded" style="max-width:90%; white-space:pre-wrap;"></div>')
      .addClass(isUser ? 'bg-primary text-white' : 'bg-white border')
      .text(text);
    wrap.append(bubble);
    $('#ad-chat-log').append(wrap);
    var log = document.getElementById('ad-chat-log');
    log.scrollTop = log.scrollHeight;
  }

  function sendChat(msg) {
    msg = (msg || '').trim();
    if (!msg) return;
    $('#ad-chat-input').val('');
    appendBubble('user', msg);
    $('#ad-chat-status').text('Yanıt bekleniyor...');
    $('#ad-chat-send').prop('disabled', true);

    $.ajax({
      url: chatUrl,
      method: 'POST',
      data: { _token: csrf, message: msg },
      success: function (res) {
        if (res.success) {
          appendBubble('assistant', res.reply);
          $('#ad-chat-status').text('Model: ' + (res.model || ''));
        } else {
          $('#ad-chat-status').text(res.message || 'Hata');
        }
      },
      error: function (xhr) {
        var m = (xhr.responseJSON && xhr.responseJSON.message) || 'İstek başarısız';
        $('#ad-chat-status').text(m);
      },
      complete: function () {
        $('#ad-chat-send').prop('disabled', false);
      }
    });
  }

  $('#ad-chat-send').on('click', function () {
    sendChat($('#ad-chat-input').val());
  });

  $('#ad-chat-input').on('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendChat($(this).val());
    }
  });

  $('.ad-quick').on('click', function () {
    sendChat($(this).data('msg'));
  });

  $('#ad-clear-chat').on('click', function () {
    $.post(clearUrl, { _token: csrf }, function () {
      $('#ad-chat-log').html('<p class="text-muted mb-0" id="ad-chat-empty">Sohbet temizlendi.</p>');
      $('#ad-chat-status').text('');
    });
  });

  $('#ad-image-gen').on('click', function () {
    var prompt = ($('#ad-image-prompt').val() || '').trim();
    if (prompt.length < 8) {
      $('#ad-image-status').text('Prompt en az 8 karakter olmalı.');
      return;
    }
    $('#ad-image-status').text('Görsel üretiliyor (30–90 sn sürebilir)...');
    $('#ad-image-gen').prop('disabled', true);

    $.ajax({
      url: imageUrl,
      method: 'POST',
      data: {
        _token: csrf,
        prompt: prompt,
        size: $('#ad-image-size').val()
      },
      success: function (res) {
        if (res.success) {
          $('#ad-image-preview').attr('src', res.url);
          $('#ad-image-link').attr('href', res.url);
          $('#ad-image-result').show();
          $('#ad-image-status').text('Hazır · ' + (res.model || ''));
          appendBubble('user', '[Görsel] ' + prompt);
          appendBubble('assistant', 'Görsel üretildi: ' + res.url);
        } else {
          $('#ad-image-status').text(res.message || 'Hata');
        }
      },
      error: function (xhr) {
        var m = (xhr.responseJSON && xhr.responseJSON.message) || 'Görsel isteği başarısız';
        $('#ad-image-status').text(m);
      },
      complete: function () {
        $('#ad-image-gen').prop('disabled', false);
      }
    });
  });
})();
</script>
@endsection

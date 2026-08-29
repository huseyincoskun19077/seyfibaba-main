@php
    $reminderOptions = $reminderOptions ?? [];
@endphp

@if(! empty($reminderOptions))
    <div class="{{ $wrapperClass ?? 'mt-3' }} border rounded p-3">
        <h6 class="mb-2"><i class="fas fa-bell"></i> Hatırlatma SMS</h6>
        <p class="text-muted small mb-3">
            Duruma uygun şablonu seçin. Metinleri yönetici panelinden
            <strong>Bildirimler → SMS Şablonları</strong> bölümünden düzenleyebilirsiniz.
        </p>

        @foreach($reminderOptions as $option)
            <form method="POST"
                  action="{{ $sendReminderRoute }}"
                  class="d-flex flex-wrap align-items-center mb-2 pb-2 {{ ! $loop->last ? 'border-bottom' : '' }}"
                  onsubmit="return confirm('{{ $option['label'] }} SMS\'i gönderilsin mi?');">
                @csrf
                <input type="hidden" name="template_slug" value="{{ $option['slug'] }}">
                <div class="flex-grow-1 pr-2">
                    <strong>{{ $option['label'] }}</strong>
                    <small class="text-muted d-block">{{ $option['hint'] }}</small>
                </div>
                <button type="submit" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-paper-plane"></i> Gönder
                </button>
            </form>
        @endforeach
    </div>
@endif

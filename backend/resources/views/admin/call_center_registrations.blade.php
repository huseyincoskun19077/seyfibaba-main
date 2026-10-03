@extends('admin.master_layout')

@section('title')
<title>Çağrı Merkezi Kayıtları</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Çağrı Merkezi Kayıtları</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('admin.Dashboard') }}</a></div>
                <div class="breadcrumb-item">Çağrı Merkezi Kayıtları</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                @foreach($agents as $agent)
                    @php $stat = $agentStats[$agent->id] ?? null; @endphp
                    <div class="col-md-4">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ $agent->name }}</h4>
                                </div>
                                <div class="card-body" style="font-size: 14px; line-height: 1.7;">
                                    <div>Kaydettiği satıcı: <strong>{{ (int) ($stat->seller_count ?? 0) }}</strong></div>
                                    <div>Yüklenen ürün: <strong>{{ (int) ($stat->product_count ?? 0) }}</strong></div>
                                    <div>KYC onaylı: <strong>{{ (int) ($stat->approved_kyc_count ?? 0) }}</strong></div>
                                    <div class="mt-2 pt-2 border-top">
                                        <div>Hakediş (toplam): <strong>{{ number_format($stat->commission_calculated ?? 0, 2, ',', '.') }} TL</strong></div>
                                        <div>Ödenen: <strong class="text-success">{{ number_format($stat->commission_paid ?? 0, 2, ',', '.') }} TL</strong></div>
                                        <div>Onay bekleyen: <strong class="text-warning">{{ number_format($stat->commission_pending ?? 0, 2, ',', '.') }} TL</strong></div>
                                        <div>Ödeme bekleyen: <strong class="text-info">{{ number_format($stat->commission_awaiting ?? 0, 2, ',', '.') }} TL</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.call-center-registrations.index') }}" class="form-row mb-3">
                        <div class="form-group col-md-3">
                            <label>Çağrı Merkezi Kullanıcısı</label>
                            <select name="agent_id" class="form-control">
                                <option value="">Tümü</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(request('agent_id') == $agent->id)>
                                        {{ $agent->name }} ({{ $agent->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ara</label>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Firma, müşteri, e-posta, telefon">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Takip</label>
                            <select name="follow_up" class="form-control">
                                <option value="">Tümü</option>
                                <option value="whatsapp" @selected(request('follow_up') === 'whatsapp')>
                                    WhatsApp: giriş yok veya ürün yok
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                        </div>
                    </form>

                    <div class="alert alert-light border mb-4">
                        <label for="cc-wa-text" class="font-weight-bold mb-1">WhatsApp mesajı</label>
                        <textarea id="cc-wa-text" class="form-control" rows="3" placeholder="Metni buraya yazın. Satırdaki WhatsApp butonuna basınca o kişinin sohbeti açılır. İsterseniz {ad} ve {firma} kullanın."></textarea>
                        <small class="text-muted d-block mt-1">
                            Toplu gönderim yok; her buton yalnızca o satırdaki isme gider. Metin tarayıcıda saklanır. Boş bırakırsanız sohbet boş açılır.
                        </small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Çağrı Merkezi</th>
                                    <th>Firma</th>
                                    <th>Müşteri / Yetkili</th>
                                    <th>Telefon</th>
                                    <th>E-posta</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>Sözleşme</th>
                                    <th>Ürün</th>
                                    <th>KYC</th>
                                    <th>Hakediş</th>
                                    <th>Durum</th>
                                    <th>Giriş</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registrations as $registration)
                                    @php
                                        $onboarding = $registration->onboarding ?? [];
                                        $needsWa = (bool) ($registration->needs_whatsapp_followup ?? false);
                                        $waId = (string) ($registration->whatsapp_id ?? '');
                                    @endphp
                                    <tr>
                                        <td>{{ $registration->id }}</td>
                                        <td>
                                            @if($registration->registeredByAdmin)
                                                <strong>{{ $registration->registeredByAdmin->name }}</strong><br>
                                                <small class="text-muted">{{ $registration->registeredByAdmin->email }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $registration->shop_name }}</td>
                                        <td>{{ $registration->user?->name }}</td>
                                        <td>{{ $registration->phone }}</td>
                                        <td>{{ $registration->email }}</td>
                                        <td>{{ $registration->created_at?->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if($registration->seller_terms_accepted_at)
                                                <span class="badge badge-success">Onaylı</span>
                                            @else
                                                <span class="badge badge-warning">Bekliyor</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ (int) $registration->products_count }}</span>
                                        </td>
                                        <td>
                                            @if($registration->kyc_status === 'approved')
                                                <span class="badge badge-success">Onaylı</span>
                                            @elseif($registration->kyc_status === 'pending')
                                                <span class="badge badge-warning">İnceleniyor</span>
                                            @elseif($registration->kyc_status === 'rejected')
                                                <span class="badge badge-danger">Reddedildi</span>
                                            @else
                                                <span class="badge badge-secondary">Yok</span>
                                            @endif
                                        </td>
                                        @php
                                            $commission = $registration->callCenterCommission;
                                            $calcTotal = (float) ($commission->calculated_total ?? 0);
                                            $paidTotal = (float) ($commission->paid_total ?? 0);
                                            $pending = max(0, $calcTotal - $paidTotal);
                                        @endphp
                                        <td>
                                            @if($calcTotal > 0)
                                                <strong>{{ number_format($calcTotal, 2, ',', '.') }} TL</strong>
                                                @if($paidTotal > 0)
                                                    <br><small class="text-muted">Ödenen: {{ number_format($paidTotal, 2, ',', '.') }} TL</small>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($commission && $commission->isAwaitingPayment())
                                                <span class="badge badge-info">Ödeme bekliyor</span>
                                                <br><small>{{ number_format((float) $commission->approved_amount, 2, ',', '.') }} TL</small>
                                            @elseif($pending > 0)
                                                <span class="badge badge-warning">Temsilci onayı</span>
                                            @elseif($paidTotal > 0 && $pending <= 0)
                                                <span class="badge badge-success">Ödendi</span>
                                            @else
                                                <span class="badge badge-secondary">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $onboarding['summary_badge'] ?? 'secondary' }}">
                                                {{ $onboarding['summary'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            @if($needsWa && $waId !== '')
                                                <button type="button"
                                                        class="btn btn-sm btn-success js-cc-whatsapp"
                                                        data-phone="{{ $waId }}"
                                                        data-name="{{ $registration->user?->name }}"
                                                        data-shop="{{ $registration->shop_name }}"
                                                        title="{{ $registration->user?->name ?: $registration->shop_name }} kişisine WhatsApp">
                                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                                </button>
                                            @elseif($needsWa)
                                                <span class="text-muted small d-block mb-1">Telefon yok</span>
                                            @endif
                                            <a href="{{ route('admin.seller-show', $registration->id) }}" class="btn btn-sm btn-primary">Detay</a>
                                            @if($onboarding['can_resend_sms'] ?? false)
                                                <form method="POST"
                                                      action="{{ route('admin.call-center-registrations.resend-sms', $registration->id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('SMS yeniden gönderilsin mi?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning">SMS</button>
                                                </form>
                                            @endif
                                            @if($commission && $commission->isAwaitingPayment())
                                                <form method="POST"
                                                      action="{{ route('admin.call-center-registrations.pay-commission', $registration->id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ number_format((float) $commission->approved_amount, 2, ',', '.') }} TL hakediş ödendi olarak işaretlensin mi?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Öde</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($registrations->hasPages())
                        <div class="mt-3">{{ $registrations->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
(function () {
    var box = document.getElementById('cc-wa-text');
    var storageKey = 'cc-wa-text';

    if (box) {
        try {
            var saved = localStorage.getItem(storageKey);
            if (saved) {
                box.value = saved;
            }
        } catch (e) {}

        box.addEventListener('input', function () {
            try {
                localStorage.setItem(storageKey, box.value);
            } catch (e) {}
        });
    }

    document.querySelectorAll('.js-cc-whatsapp').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var phone = (btn.getAttribute('data-phone') || '').replace(/\D/g, '');
            if (!phone) {
                alert('Bu kayıtta geçerli telefon yok.');
                return;
            }

            var name = btn.getAttribute('data-name') || '';
            var shop = btn.getAttribute('data-shop') || '';
            var tpl = box ? box.value : '';
            var text = tpl.split('{ad}').join(name).split('{firma}').join(shop).trim();
            var url = 'https://wa.me/' + phone;
            if (text) {
                url += '?text=' + encodeURIComponent(text);
            }

            window.open(url, '_blank');
        });
    });
})();
</script>
@endsection

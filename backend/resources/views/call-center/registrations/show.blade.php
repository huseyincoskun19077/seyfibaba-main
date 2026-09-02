@extends('call-center.layout.master')

@section('title')
<title>Kayıt Detayı</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Kayıt Detayı</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.registrations.index') }}">Kayıtlarım</a></div>
                <div class="breadcrumb-item active">{{ $vendor->shop_name }}</div>
            </div>
        </div>

        @if($registrationResult)
            @php
                $emailSkipped = (bool) ($registrationResult['email_skipped'] ?? false);
                $alertType = 'success';
                if (! $emailSkipped && !($registrationResult['email_sent'] ?? false)) {
                    $alertType = ($registrationResult['sms_sent'] ?? false) ? 'warning' : 'danger';
                }
            @endphp
            <div class="alert alert-{{ $alertType }}">
                <h5 class="mb-2">Kayıt başarıyla oluşturuldu.</h5>
                @if($registrationResult['was_existing_user'])
                    <p class="mb-1">Mevcut müşteri hesabına satıcı profili eklendi.</p>
                @endif
                @if($emailSkipped)
                    <p class="mb-1">E-posta girilmedi. Satıcı daha sonra panelden (Profil / Mağaza) ekleyebilir.</p>
                @elseif($registrationResult['email_sent'] ?? false)
                    <p class="mb-1">Giriş adresi ve hesap bilgisi <strong>{{ $registrationResult['email'] }}</strong> adresine e-posta ile gönderildi.</p>
                @else
                    <p class="mb-1 text-danger">
                        E-posta gönderilemedi.
                        @if(!empty($registrationResult['email_error']))
                            <strong>{{ $registrationResult['email_error'] }}</strong>
                        @endif
                    </p>
                @endif
                @if($registrationResult['sms_sent'] ?? false)
                    <p class="mb-0">Tek kullanımlık giriş şifresi SMS ile gönderildi.</p>
                @elseif(!empty($registrationResult['sms_error']))
                    <p class="mb-0 text-danger">SMS gönderilemedi: {{ $registrationResult['sms_error'] }}</p>
                @endif
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @include('call-center.partials.registration_edit_form', [
                    'vendor' => $vendor,
                    'onboarding' => $onboarding,
                    'updateRoute' => route('call-center.registrations.update', $vendor->id),
                    'currentPhone' => $vendor->user?->phone ?? $vendor->phone,
                    'wrapperClass' => 'mb-4',
                ])

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Firma:</strong> {{ $vendor->shop_name }}</p>
                        <p><strong>Yetkili:</strong> {{ $vendor->user?->name }}</p>
                        <p><strong>Telefon:</strong> {{ $vendor->phone }}</p>
                        <p><strong>E-posta:</strong>
                            @if(\App\Services\CallCenter\QuickSellerRegistrationService::isPendingEmail($vendor->user?->email) || empty($vendor->email))
                                <span class="text-muted">Henüz girilmedi</span>
                            @else
                                {{ $vendor->email ?: $vendor->user?->email }}
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Adres:</strong> {{ $vendor->address }}</p>
                        <p><strong>Durum:</strong> <span class="badge badge-success">Aktif Satıcı</span></p>
                        <p><strong>Kayıt Tarihi:</strong> {{ $vendor->created_at?->format('d.m.Y H:i') }}</p>
                    </div>
                </div>

                @if($vendor->quick_registration_note)
                    <hr>
                    <p class="mb-0"><strong>Not:</strong> {{ $vendor->quick_registration_note }}</p>
                @endif

                @include('call-center.partials.seller_progress', [
                    'vendor' => $vendor,
                    'productCount' => $vendor->products_count ?? 0,
                    'wrapperClass' => 'mt-3',
                ])

                @include('call-center.partials.reminder_sms', [
                    'reminderOptions' => $reminderOptions ?? [],
                    'sendReminderRoute' => route('call-center.registrations.send-reminder', $vendor->id),
                    'wrapperClass' => 'mt-3',
                ])

                @include('call-center.partials.onboarding_status', [
                    'vendor' => $vendor,
                    'onboarding' => $onboarding,
                    'wrapperClass' => 'mt-3',
                    'resendSmsRoute' => route('call-center.registrations.resend-sms', $vendor->id),
                    'resendEmailRoute' => route('call-center.registrations.resend-email', $vendor->id),
                ])

                <hr>
                <a href="{{ route('call-center.registrations.create') }}" class="btn btn-outline-primary">Yeni Kayıt</a>
            </div>
        </div>
    </section>
</div>
@endsection

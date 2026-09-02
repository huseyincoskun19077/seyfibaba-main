@php
    $onboarding = $onboarding ?? \App\Services\CallCenter\QuickSellerOnboardingStatus::for($vendor);
@endphp

@if($onboarding['applicable'])
    <div class="{{ $wrapperClass ?? 'mt-3' }}">
        <h6 class="mb-2">{{ $title ?? 'Aktivasyon Durumu' }}</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-2">
                <tr>
                    <td style="width:40%">SMS</td>
                    <td>
                        @if($onboarding['sms_sent'] === true)
                            <span class="badge badge-success">Gönderildi</span>
                            @if($onboarding['sms_sent_at'])
                                <small class="text-muted d-block">{{ $onboarding['sms_sent_at'] }}</small>
                            @endif
                        @elseif($onboarding['sms_sent'] === false)
                            <span class="badge badge-danger">Gönderilemedi</span>
                        @else
                            <span class="badge badge-secondary">Eski kayıt / bilinmiyor</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>E-posta</td>
                    <td>
                        @if($onboarding['email_skipped'])
                            <span class="badge badge-light">Girilmedi (SMS ile devam)</span>
                        @elseif($onboarding['email_sent'] === true)
                            <span class="badge badge-success">Gönderildi</span>
                            @if($onboarding['email_sent_at'])
                                <small class="text-muted d-block">{{ $onboarding['email_sent_at'] }}</small>
                            @endif
                        @elseif($onboarding['email_sent'] === false)
                            <span class="badge badge-danger">Gönderilemedi</span>
                        @else
                            <span class="badge badge-secondary">Eski kayıt / bilinmiyor</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Sisteme giriş</td>
                    <td>
                        @if($onboarding['logged_in'])
                            <span class="badge badge-success">Giriş yaptı</span>
                            @if($onboarding['logged_in_at'])
                                <small class="text-muted d-block">{{ $onboarding['logged_in_at'] }}</small>
                            @endif
                        @else
                            <span class="badge badge-warning">Henüz girmedi</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Yeni şifre</td>
                    <td>
                        @if($onboarding['password_changed'])
                            <span class="badge badge-success">Oluşturuldu</span>
                        @else
                            <span class="badge badge-warning">Oluşturulmadı</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Özet</td>
                    <td>
                        <span class="badge badge-{{ $onboarding['summary_badge'] }}">{{ $onboarding['summary'] }}</span>
                    </td>
                </tr>
            </table>
        </div>

        @if(($onboarding['can_resend_sms'] ?? false) && ! empty($resendSmsRoute ?? null))
            <form method="POST" action="{{ $resendSmsRoute }}" class="mb-3"
                  onsubmit="return confirm('Hoş geldin SMS\'i aynı giriş şifresiyle yeniden gönderilsin mi?');">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fas fa-sms"></i> SMS Yeniden Gönder
                </button>
                <small class="text-muted d-block mt-1">
                    Satıcı yeni şifresini oluşturmadıysa kullanılabilir. Mevcut tek girişlik şifre değişmez.
                </small>
            </form>
        @endif

        @if(($onboarding['can_resend_email'] ?? false) && ! empty($resendEmailRoute ?? null))
            <form method="POST" action="{{ $resendEmailRoute }}" class="mb-3"
                  onsubmit="return confirm('Hoş geldin e-postası aynı giriş şifresiyle yeniden gönderilsin mi?');">
                @csrf
                <button type="submit" class="btn btn-info btn-sm">
                    <i class="fas fa-envelope"></i> E-posta Yeniden Gönder
                </button>
                <small class="text-muted d-block mt-1">
                    Giriş bilgileri e-posta ile yeniden iletilir. Mevcut tek girişlik şifre değişmez.
                </small>
            </form>
        @elseif(($onboarding['password_changed'] ?? false) && ! ($onboarding['can_resend_sms'] ?? false))
            <p class="text-muted mb-0 small">Yeni şifre oluşturulduğu için hoş geldin bildirimi gönderilemez.</p>
        @endif
    </div>
@endif

@extends('call-center.layout.master')

@section('title')
<title>Kayıtlarım</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Kayıtlarım</h1>
            <div class="section-header-button">
                <a href="{{ route('call-center.registrations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Hızlı Kayıt
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Firma</th>
                                <th>Yetkili</th>
                                <th>Telefon</th>
                                <th>SMS</th>
                                <th>Giriş / Şifre</th>
                                <th>Ürün</th>
                                <th>KYC</th>
                                <th>Tarih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrations as $registration)
                                @php
                                    $onboarding = $registration->onboarding ?? [];
                                    $kycStatus = $registration->kyc_status ?? 'not_submitted';
                                @endphp
                                <tr>
                                    <td>{{ $registration->id }}</td>
                                    <td>{{ $registration->shop_name }}</td>
                                    <td>{{ $registration->user?->name }}</td>
                                    <td>{{ $registration->phone }}</td>
                                    <td>
                                        @if(($onboarding['sms_sent'] ?? null) === true)
                                            <span class="badge badge-success">Gitti</span>
                                        @elseif(($onboarding['sms_sent'] ?? null) === false)
                                            <span class="badge badge-danger">Gitmedi</span>
                                        @else
                                            <span class="badge badge-secondary">?</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $onboarding['summary_badge'] ?? 'secondary' }}">
                                            {{ $onboarding['summary'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if((int) ($registration->products_count ?? 0) > 0)
                                            <span class="badge badge-success">{{ (int) $registration->products_count }}</span>
                                        @else
                                            <span class="badge badge-warning">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($kycStatus === 'approved')
                                            <span class="badge badge-success">Onaylı</span>
                                        @elseif($kycStatus === 'pending')
                                            <span class="badge badge-warning">İnceleniyor</span>
                                        @elseif($kycStatus === 'rejected')
                                            <span class="badge badge-danger">Reddedildi</span>
                                        @else
                                            <span class="badge badge-secondary">Yok</span>
                                        @endif
                                    </td>
                                    <td>{{ $registration->created_at?->format('d.m.Y H:i') }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('call-center.registrations.show', $registration->id) }}" class="btn btn-sm btn-primary">Detay</a>
                                        @if($onboarding['can_resend_sms'] ?? false)
                                            <form method="POST"
                                                  action="{{ route('call-center.registrations.resend-sms', $registration->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('SMS yeniden gönderilsin mi?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning">SMS</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Henüz kayıt yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($registrations->hasPages())
                <div class="card-footer">{{ $registrations->links() }}</div>
            @endif
        </div>
    </section>
</div>
@endsection

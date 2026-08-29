@extends('admin.master_layout')
@section('title')
<title>{{ $salon->name }} — Salon CRM</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>{{ $salon->name }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.salon-crm.index') }}">Salon CRM</a></div>
        <div class="breadcrumb-item">{{ $salon->name }}</div>
      </div>
    </div>

    <div class="section-body">
      <a href="{{ route('admin.salon-crm.index') }}" class="btn btn-primary mb-3"><i class="fas fa-list"></i> Salon Listesi</a>

      @php
        $access = $snapshot['access'] ?? [];
        $reason = $access['reason'] ?? 'locked';
        $reasonLabel = match($reason) {
          'admin_free' => 'Admin ücretsiz',
          'trial' => 'Deneme süresi',
          'next_month_credit' => 'Ay kredisi',
          'immediate_unlock' => 'Alışveriş eşiği',
          default => 'Kilitli',
        };
      @endphp

      <div class="row">
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-calendar-day"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Kullanım Süresi</h4></div>
              <div class="card-body">{{ $daysActive }} gün</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-users"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Personel</h4></div>
              <div class="card-body">{{ $salon->staff_count }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-user-friends"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Müşteri</h4></div>
              <div class="card-body">{{ $salon->customers_count }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-calendar-check"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bu Ay Randevu</h4></div>
              <div class="card-body">{{ $appointmentStats['this_month'] }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-5">
          <div class="card">
            <div class="card-header"><h4>Salon Bilgileri</h4></div>
            <div class="card-body">
              <table class="table table-sm table-borderless mb-0">
                <tr><th width="140">Salon adı</th><td>{{ $salon->name }}</td></tr>
                <tr><th>Patron</th><td>{{ $salon->owner_name }}</td></tr>
                <tr><th>Kullanıcı adı</th><td>{{ $salon->owner_username }}</td></tr>
                <tr><th>Tür</th><td>{{ $salon->type === 'guzellik' ? 'Güzellik salonu' : 'Kuaför' }}</td></tr>
                <tr><th>Telefon</th><td>{{ $salon->phone ?: '—' }}</td></tr>
                <tr><th>Hesap</th><td>{{ $salon->user->email ?? 'Bağlı değil' }}</td></tr>
                <tr><th>Kayıt</th><td>{{ optional($salon->created_at)->format('d.m.Y H:i') }}</td></tr>
                <tr><th>Deneme bitiş</th><td>{{ optional($salon->trial_ends_at)->format('d.m.Y') ?: '—' }}</td></tr>
                <tr><th>Admin ücretsiz</th><td>{{ optional($salon->admin_free_until)->format('d.m.Y') ?: '—' }}</td></tr>
                <tr><th>Erişim durumu</th><td><strong>{{ $reasonLabel }}</strong></td></tr>
                <tr><th>Bu ay alışveriş</th><td>{{ number_format($monthSpend, 0, ',', '.') }} TL</td></tr>
                <tr><th>Eşik</th><td>{{ number_format($access['threshold'] ?? 10000, 0, ',', '.') }} TL</td></tr>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h4>Erişim Yönetimi</h4></div>
            <div class="card-body">
              <p class="text-muted small mb-3">Berbere ücretsiz CRM vermek için süre seçin veya kilidi kaldırın.</p>
              <form action="{{ route('admin.salon-crm.update-access', $salon->id) }}" method="POST" class="mb-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="admin_notes" value="{{ $salon->admin_notes }}">
                <div class="btn-group btn-group-sm flex-wrap mb-2" role="group">
                  <button type="submit" name="action" value="free_1m" class="btn btn-success">1 ay ücretsiz</button>
                  <button type="submit" name="action" value="free_3m" class="btn btn-success">3 ay</button>
                  <button type="submit" name="action" value="free_6m" class="btn btn-success">6 ay</button>
                  <button type="submit" name="action" value="free_12m" class="btn btn-success">12 ay</button>
                  <button type="submit" name="action" value="free_forever" class="btn btn-dark">Süresiz</button>
                  <button type="submit" name="action" value="remove_free" class="btn btn-outline-danger" onclick="return confirm('Admin ücretsiz erişim kaldırılsın mı?')">Kilidi geri al</button>
                </div>
              </form>
              <form action="{{ route('admin.salon-crm.update-access', $salon->id) }}" method="POST" class="mb-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="admin_notes" value="{{ $salon->admin_notes }}">
                <div class="form-row align-items-end">
                  <div class="col-md-7">
                    <label>Özel bitiş tarihi</label>
                    <input type="date" name="admin_free_until" class="form-control" value="{{ optional($salon->admin_free_until)->format('Y-m-d') }}">
                  </div>
                  <div class="col-md-5">
                    <button type="submit" name="action" value="custom_date" class="btn btn-primary btn-block">Tarihi kaydet</button>
                  </div>
                </div>
              </form>
              <form action="{{ route('admin.salon-crm.update-access', $salon->id) }}" method="POST" class="mb-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="admin_notes" value="{{ $salon->admin_notes }}">
                <div class="btn-group btn-group-sm">
                  <button type="submit" name="action" value="extend_trial_30" class="btn btn-info">Deneme +30 gün</button>
                  <button type="submit" name="action" value="extend_trial_90" class="btn btn-info">Deneme +90 gün</button>
                </div>
              </form>
              <form action="{{ route('admin.salon-crm.update-access', $salon->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="save_notes">
                <div class="form-group mb-0">
                  <label>Admin notu</label>
                  <textarea name="admin_notes" rows="3" class="form-control">{{ $salon->admin_notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm mt-2">Notu kaydet</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card">
            <div class="card-header"><h4>Personel ({{ $salon->staff->count() }})</h4></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Ad</th>
                      <th>Kullanıcı adı</th>
                      <th>Durum</th>
                      <th>Kayıt</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($salon->staff as $member)
                      <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->username }}</td>
                        <td>
                          @if($member->is_active)
                            <span class="badge badge-success">Aktif</span>
                          @else
                            <span class="badge badge-secondary">Pasif</span>
                          @endif
                        </td>
                        <td>{{ optional($member->created_at)->format('d.m.Y') }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted p-3">Personel eklenmemiş.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h4>Son Randevular</h4></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Tarih</th>
                      <th>Müşteri</th>
                      <th>Hizmet</th>
                      <th>Personel</th>
                      <th>Durum</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentAppointments as $appt)
                      <tr>
                        <td>{{ optional($appt->starts_at)->format('d.m.Y H:i') }}</td>
                        <td>{{ $appt->customer_name }}</td>
                        <td>{{ $appt->service_name }}</td>
                        <td>{{ $appt->staff->name ?? '—' }}</td>
                        <td>{{ $appt->status }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted p-3">Randevu yok.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          @if($salon->grants->isNotEmpty())
          <div class="card">
            <div class="card-header"><h4>Erişim Geçmişi</h4></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr><th>Dönem</th><th>Tür</th><th>Tutar</th><th>Tarih</th></tr>
                  </thead>
                  <tbody>
                    @foreach($salon->grants as $grant)
                      <tr>
                        <td>{{ $grant->period }}</td>
                        <td>{{ $grant->type }}</td>
                        <td>{{ number_format($grant->qualified_amount, 0, ',', '.') }} TL</td>
                        <td>{{ optional($grant->created_at)->format('d.m.Y') }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

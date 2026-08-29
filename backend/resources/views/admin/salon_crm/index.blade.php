@extends('admin.master_layout')
@section('title')
<title>Salon CRM</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Salon CRM</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Salon CRM</div>
      </div>
    </div>

    <div class="section-body">
      @if(!empty($migrationMissing))
        <div class="alert alert-warning">
          Salon CRM tabloları henüz kurulmamış. Sunucuda <code>php artisan migrate</code> çalıştırın.
        </div>
      @else
        <div class="row">
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-primary"><i class="fas fa-store"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Toplam Salon</h4></div>
                <div class="card-body">{{ $stats['total_salons'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-success"><i class="fas fa-gift"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Admin Ücretsiz</h4></div>
                <div class="card-body">{{ $stats['admin_free'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-info"><i class="fas fa-clock"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Deneme Süresinde</h4></div>
                <div class="card-body">{{ $stats['in_trial'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-warning"><i class="fas fa-users"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Toplam Personel</h4></div>
                <div class="card-body">{{ $stats['total_staff'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-secondary"><i class="fas fa-user-friends"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Salon Müşterisi</h4></div>
                <div class="card-body">{{ $stats['total_customers'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-danger"><i class="fas fa-calendar-check"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Bu Ay Randevu</h4></div>
                <div class="card-body">{{ $stats['appointments_this_month'] ?? 0 }}</div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
              <div class="card-icon bg-dark"><i class="fas fa-calendar-alt"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>Toplam Randevu</h4></div>
                <div class="card-body">{{ $stats['total_appointments'] ?? 0 }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>Salon Listesi</h4>
            <div class="card-header-form">
              <form method="GET" action="{{ route('admin.salon-crm.index') }}" class="form-inline">
                <select name="filter" class="form-control mr-2">
                  <option value="">Tümü</option>
                  <option value="unlocked" @selected(request('filter') === 'unlocked')>Açık</option>
                  <option value="locked" @selected(request('filter') === 'locked')>Kilitli</option>
                  <option value="admin_free" @selected(request('filter') === 'admin_free')>Admin ücretsiz</option>
                  <option value="trial" @selected(request('filter') === 'trial')>Deneme</option>
                </select>
                <input type="text" name="q" class="form-control" placeholder="Salon, patron, e-posta..." value="{{ request('q') }}">
                <button type="submit" class="btn btn-primary ml-2">Ara</button>
              </form>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Salon</th>
                    <th>Patron</th>
                    <th>Tür</th>
                    <th>Kullanım</th>
                    <th>Erişim</th>
                    <th>Personel</th>
                    <th>Müşteri</th>
                    <th>Randevu</th>
                    <th>Kayıt</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($salons as $salon)
                    @php
                      $access = $salon->access_snapshot['access'] ?? [];
                      $reason = $access['reason'] ?? 'locked';
                      $badge = match($reason) {
                        'admin_free' => 'success',
                        'trial' => 'info',
                        'next_month_credit', 'immediate_unlock' => 'primary',
                        default => 'secondary',
                      };
                      $label = match($reason) {
                        'admin_free' => 'Admin ücretsiz',
                        'trial' => 'Deneme',
                        'next_month_credit' => 'Ay kredisi',
                        'immediate_unlock' => 'Alışveriş ile açık',
                        default => 'Kilitli',
                      };
                    @endphp
                    <tr>
                      <td>{{ $salon->id }}</td>
                      <td>
                        <strong>{{ $salon->name }}</strong><br>
                        <small class="text-muted">{{ $salon->owner_username }}</small>
                      </td>
                      <td>
                        {{ $salon->owner_name }}<br>
                        <small class="text-muted">{{ $salon->user->email ?? '—' }}</small>
                      </td>
                      <td>{{ $salon->type === 'guzellik' ? 'Güzellik' : 'Kuaför' }}</td>
                      <td>{{ $salon->days_active }} gün</td>
                      <td><span class="badge badge-{{ $badge }}">{{ $label }}</span></td>
                      <td>{{ $salon->staff_count }}</td>
                      <td>{{ $salon->customers_count }}</td>
                      <td>{{ $salon->appointments_count }}</td>
                      <td>{{ optional($salon->created_at)->format('d.m.Y') }}</td>
                      <td>
                        <a href="{{ route('admin.salon-crm.show', $salon->id) }}" class="btn btn-sm btn-primary">Detay</a>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="11" class="text-center text-muted">Henüz salon kaydı yok.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            {{ $salons->links() }}
          </div>
        </div>
      @endif
    </div>
  </section>
</div>
@endsection

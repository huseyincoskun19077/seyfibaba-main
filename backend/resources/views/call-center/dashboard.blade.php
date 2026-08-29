@extends('call-center.layout.master')

@section('title')
<title>Çağrı Merkezi Paneli</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Panel</h1>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Bugünkü Kayıt</h4></div>
                        <div class="card-body">{{ $todayCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success"><i class="fas fa-store"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Toplam Kayıt</h4></div>
                        <div class="card-body">{{ $totalCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning"><i class="fas fa-user-plus"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Hızlı İşlem</h4></div>
                        <div class="card-body">
                            <a href="{{ route('call-center.registrations.create') }}" class="btn btn-sm btn-primary">Yeni Kayıt</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4>Son Kayıtlar</h4></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Firma</th>
                                <th>Yetkili</th>
                                <th>Telefon</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRegistrations as $registration)
                                <tr>
                                    <td>
                                        <a href="{{ route('call-center.registrations.show', $registration->id) }}">
                                            {{ $registration->shop_name }}
                                        </a>
                                    </td>
                                    <td>{{ $registration->user?->name }}</td>
                                    <td>{{ $registration->phone }}</td>
                                    <td>{{ $registration->created_at?->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Henüz kayıt yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

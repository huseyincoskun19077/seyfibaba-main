@extends('admin.master_layout')
@section('title')
<title>İkinci El</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">İkinci El</div>
      </div>
    </div>

    <div class="section-body">
          <div class="row mb-4">
            <div class="col-md-2 mb-3">
              <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                  <div class="card-header"><h4>Yayında</h4></div>
                  <div class="card-body">{{ $stats['active'] }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-2 mb-3">
              <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                  <div class="card-header"><h4>Onay bekleyen</h4></div>
                  <div class="card-body">{{ $stats['pending'] }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-2 mb-3">
              <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                  <div class="card-header"><h4>Öne çıkan</h4></div>
                  <div class="card-body">{{ $stats['featured'] }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-2 mb-3">
              <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                  <div class="card-header"><h4>Acil</h4></div>
                  <div class="card-body">{{ $stats['urgent'] }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                  <div class="card-header"><h4>Toplam görüntülenme</h4></div>
                  <div class="card-body">{{ number_format($stats['views']) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4>Yönetim</h4>
            </div>
            <div class="card-body">
              <div class="row">
            <div class="col-md-3 mb-3">
              <a class="btn btn-primary btn-block" href="{{ route('admin.second-hand.verifications') }}">Doğrulamalar</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-primary btn-block" href="{{ route('admin.second-hand.members') }}">Üyeler</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-primary btn-block" href="{{ route('admin.second-hand.listings') }}">İlanlar</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-primary btn-block" href="{{ route('admin.second-hand.reports') }}">Raporlar</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-warning btn-block" href="{{ route('admin.second-hand.moderation-logs') }}">Argo Denemeleri</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-success btn-block" href="{{ route('admin.second-hand.homepage') }}">Anasayfa</a>
            </div>
            <div class="col-md-3 mb-3">
              <a class="btn btn-dark btn-block" href="{{ route('admin.second-hand.agreements') }}">Sözleşmeler</a>
            </div>
          </div>
          <div class="table-responsive mt-4">
            <h5>En çok görüntülenen ilanlar</h5>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>İlan</th>
                  <th>Görüntülenme</th>
                  <th>Özellik</th>
                </tr>
              </thead>
              <tbody>
                @forelse($topViewed as $item)
                  <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ (int) $item->views_count }}</td>
                    <td>
                      @if($item->is_featured)<span class="badge badge-warning">Öne çıkan</span>@endif
                      @if($item->is_urgent)<span class="badge badge-danger">Acil</span>@endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3">Henüz ilan yok.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="alert alert-info mb-0">
            İkinci el doğrulama, ilan onay, öne çıkan / acil işaretleme ve sözleşmeler bu menüden yönetilir.
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection


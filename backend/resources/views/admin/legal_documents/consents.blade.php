@extends('admin.master_layout')
@section('title')
<title>Onay Kayıtları — {{ $legal_document->title }}</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Onay Kayıtları: {{ $legal_document->title }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.legal-documents.index') }}">Yasal Belgeler</a></div>
        <div class="breadcrumb-item">Onay Kayıtları</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Kullanıcı ID</th>
                      <th>Belge</th>
                      <th>Versiyon</th>
                      <th>IP</th>
                      <th>Platform</th>
                      <th>Bağlam</th>
                      <th>Durum</th>
                      <th>Tarih</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($consents as $consent)
                      <tr>
                        <td>{{ $consent->user_id ?? '—' }}</td>
                        <td>{{ $consent->document_title }}</td>
                        <td>{{ $consent->document_version }}</td>
                        <td>{{ $consent->ip_address }}</td>
                        <td>{{ strtoupper($consent->platform) }}</td>
                        <td>{{ $consent->context ?? '—' }}</td>
                        <td>{{ $consent->consent_status ? 'Onay' : 'Red' }}</td>
                        <td>{{ $consent->consented_at?->format('d.m.Y H:i:s') }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="8" class="text-center">Henüz onay kaydı yok.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              {{ $consents->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

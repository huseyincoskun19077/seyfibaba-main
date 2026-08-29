@extends('admin.master_layout')
@section('title')
<title>Yasal Belgeler</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Yasal Belgeler</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Yasal Belgeler</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4>Sözleşmeler ve Hukuki Metinler</h4>
              <a href="{{ route('admin.legal-documents.create') }}" class="btn btn-primary btn-sm">Yeni Belge</a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Başlık</th>
                      <th>Slug</th>
                      <th>Versiyon</th>
                      <th>Yayında</th>
                      <th>Zorunlu Onay</th>
                      <th>Aktif</th>
                      <th>Son Güncelleme</th>
                      <th>İşlem</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($documents as $document)
                      <tr>
                        <td>{{ $document->sort_order }}</td>
                        <td>{{ $document->title }}</td>
                        <td><code>/legal/{{ $document->slug }}</code></td>
                        <td>{{ $document->version }}</td>
                        <td>
                          @if($document->is_published)
                            <span class="badge badge-success">Evet</span>
                          @else
                            <span class="badge badge-secondary">Hayır</span>
                          @endif
                        </td>
                        <td>
                          @if($document->requires_consent)
                            <span class="badge badge-warning">Evet</span>
                          @else
                            <span class="badge badge-light">Hayır</span>
                          @endif
                        </td>
                        <td>
                          @if($document->is_active)
                            <span class="badge badge-success">Aktif</span>
                          @else
                            <span class="badge badge-danger">Pasif</span>
                          @endif
                        </td>
                        <td>{{ $document->updated_at?->format('d.m.Y H:i') }}</td>
                        <td>
                          <a href="{{ route('admin.legal-documents.edit', $document->id) }}" class="btn btn-primary btn-sm">Düzenle</a>
                          <a href="{{ route('admin.legal-documents.consents', $document->id) }}" class="btn btn-info btn-sm">Onay Kayıtları</a>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="9" class="text-center">Henüz belge yok. Seeder çalıştırın veya yeni belge ekleyin.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@extends('admin.master_layout')
@section('title')
<title>Müşteri Alanları</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Müşteri Alanları</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Yönetim Paneli</a></div>
        <div class="breadcrumb-item">Müşteri Alanları</div>
      </div>
    </div>
    <div class="section-body">
      <div class="alert alert-info">
        Bu katman mevcut kategori URL’lerini değiştirmez. Ürünler kopyalanmaz; aynı ürün birden fazla alana eşlenebilir.
        Önce <strong>Eşleşme önizlemesi</strong>ni inceleyin; belirsiz ürünleri kuyruktan onaylayın.
      </div>
      <div class="mb-3">
        <a href="{{ route('admin.customer-segments.preview') }}" class="btn btn-outline-primary">Eşleşme önizlemesi</a>
        <a href="{{ route('admin.customer-segments.queue') }}" class="btn btn-outline-warning">
          Belirsiz ürün kuyruğu
          @if(($pendingCount ?? 0) > 0)
            <span class="badge badge-danger">{{ $pendingCount }}</span>
          @endif
        </a>
        <form action="{{ route('admin.customer-segments.apply-unambiguous') }}" method="POST" class="d-inline" onsubmit="return confirm('Yalnızca net (tek alanlı) alt kategori eşleşmeleri uygulanacak. Devam?');">
          @csrf
          <button type="submit" class="btn btn-success">Önerilen eşleşmeleri uygula (boş olanlar hariç)</button>
        </form>
      </div>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Sıra</th>
                <th>Alan</th>
                <th>Slug</th>
                <th>Misafir ana sayfa</th>
                <th>Birincil giriş</th>
                <th>Aktif</th>
                <th>Ürün / Satıcı</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($segments as $s)
                <tr>
                  <td>{{ $s->serial }}</td>
                  <td><strong>{{ $s->name }}</strong><br><small class="text-muted">{{ $s->code }}</small></td>
                  <td><code>{{ $s->slug }}</code></td>
                  <td>{{ $s->show_on_guest_home ? 'Evet' : 'Hayır' }}</td>
                  <td>{{ $s->is_primary_home ? 'Evet' : 'Hayır' }}</td>
                  <td>{{ $s->is_active ? 'Evet' : 'Hayır' }}</td>
                  <td>
                    {{ ($segmentStats[$s->id]['products'] ?? 0) }} ürün
                    / {{ ($segmentStats[$s->id]['vendors'] ?? 0) }} satıcı
                  </td>
                  <td><a href="{{ route('admin.customer-segments.edit', $s->id) }}" class="btn btn-sm btn-primary">Düzenle</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@extends('admin.master_layout')
@section('title')
<title>Eşleşme önizlemesi</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Alt kategori → müşteri alanı önizlemesi</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.customer-segments.index') }}">Müşteri Alanları</a></div>
        <div class="breadcrumb-item">Önizleme</div>
      </div>
    </div>
    <div class="section-body">
      <div class="alert alert-warning">Bu sayfa yazmaz. “Net eşleşmeleri uygula” yalnızca tek alanlı satırları kaydeder. Belirsiz (çoklu/boş) satırlar admin incelemesine bırakılır.</div>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Üst</th>
                <th>Alt</th>
                <th>Ürün</th>
                <th>Öneri</th>
                <th>Belirsiz</th>
              </tr>
            </thead>
            <tbody>
              @foreach($preview as $r)
                <tr class="{{ $r['ambiguous'] ? 'table-warning' : '' }}">
                  <td>{{ $r['sub_id'] }}</td>
                  <td>{{ $r['category'] }}</td>
                  <td>{{ $r['sub_name'] }}</td>
                  <td>{{ $r['product_count'] }}</td>
                  <td>{{ implode(', ', $r['suggested']) ?: '—' }}</td>
                  <td>{{ $r['ambiguous'] ? 'EVET' : '' }}</td>
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

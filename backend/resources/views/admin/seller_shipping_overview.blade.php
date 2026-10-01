@extends('admin.master_layout')
@section('title')
<title>Kargo ve ürün tarihi</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Kargo ve ürün tarihi</h1>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <form method="GET" class="form-inline mb-3">
            <select name="kargo" class="form-control mr-2">
              <option value="">Tüm satıcılar</option>
              <option value="var" @selected($kargo === 'var')>Kargo kurmuş</option>
              <option value="yok" @selected($kargo === 'yok')>Kargo yok</option>
            </select>
            <button class="btn btn-primary" type="submit">Filtrele</button>
          </form>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Mağaza</th>
                  <th>Kargo</th>
                  <th>Ürün</th>
                  <th>İlk yükleme</th>
                  <th>Son güncelleme</th>
                </tr>
              </thead>
              <tbody>
                @forelse($sellers as $seller)
                  @php
                    $date = $dates->get($seller->id);
                    $first = $date->first_product_at ?? null;
                    $last = $date->last_product_at ?? null;
                  @endphp
                  <tr>
                    <td>
                      <a href="{{ route('admin.seller-show', $seller->id) }}"><strong>{{ $seller->shop_name }}</strong></a>
                    </td>
                    <td>
                      @if($seller->shippingTiers->isEmpty())
                        <span class="badge badge-warning">Kargo yok</span>
                      @else
                        @foreach($seller->shippingTiers as $tier)
                          @php
                            $from = number_format((float) $tier->min_amount, 0, ',', '.');
                            $to = $tier->max_amount === null
                              ? 'sınırsız'
                              : number_format((float) $tier->max_amount, 0, ',', '.').' TL';
                            $fee = (float) $tier->shipping_fee <= 0
                              ? 'ücretsiz'
                              : number_format((float) $tier->shipping_fee, 2, ',', '.').' TL';
                          @endphp
                          <div>{{ $from }} TL – {{ $to }}: {{ $fee }}</div>
                        @endforeach
                      @endif
                    </td>
                    <td>{{ (int) $seller->products_count }}</td>
                    <td>{{ $first ? \Illuminate\Support\Carbon::parse($first)->format('d.m.Y H:i') : '—' }}</td>
                    <td>{{ $last ? \Illuminate\Support\Carbon::parse($last)->format('d.m.Y H:i') : '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted">Satıcı yok.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

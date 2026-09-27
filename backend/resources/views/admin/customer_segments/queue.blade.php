@extends('admin.master_layout')
@section('title')
<title>Belirsiz ürün kuyruğu</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Belirsiz ürün kuyruğu</h1>
    </div>
    <div class="section-body">
      <form method="POST" action="{{ route('admin.customer-segments.queue.scan') }}" class="mb-3">
        @csrf
        <button class="btn btn-warning">Belirsiz ürünleri tara ve kuyruğa al</button>
      </form>
      <div class="card">
        <div class="card-body table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Ürün</th>
                <th>Kategori</th>
                <th>Sebep</th>
                <th>Alan seç</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $item)
                <tr>
                  <td>#{{ $item->product_id }} {{ $item->product->name ?? '' }}</td>
                  <td>{{ $item->product->category->name ?? '—' }} / {{ $item->product->subCategory->name ?? '—' }}</td>
                  <td>{{ $item->reason }}</td>
                  <td>
                    <form method="POST" action="{{ route('admin.customer-segments.queue.resolve', $item->id) }}">
                      @csrf
                      <input type="hidden" name="action" value="approve">
                      @foreach($segments as $s)
                        <label class="d-block">
                          <input type="checkbox" name="segment_ids[]" value="{{ $s->id }}"
                            @if(in_array($s->id, $item->suggested_segment_ids ?? [], true)) checked @endif>
                          {{ $s->name }}
                        </label>
                      @endforeach
                      <button class="btn btn-sm btn-success mt-1">Onayla</button>
                    </form>
                  </td>
                  <td>
                    <form method="POST" action="{{ route('admin.customer-segments.queue.resolve', $item->id) }}">
                      @csrf
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-sm btn-secondary">Reddet</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-muted text-center">Kuyruk boş. Tara butonunu kullanın.</td></tr>
              @endforelse
            </tbody>
          </table>
          {{ $items->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

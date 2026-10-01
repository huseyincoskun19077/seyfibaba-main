@extends('admin.master_layout')
@section('title')
<title>Sizi arayalım</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Sizi arayalım</h1>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Tarih</th>
                  <th>Firma</th>
                  <th>Telefon</th>
                </tr>
              </thead>
              <tbody>
                @forelse($callbacks as $row)
                  <tr>
                    <td>{{ $row->created_at ? $row->created_at->format('d.m.Y H:i') : '—' }}</td>
                    <td>{{ $row->shop_name }}</td>
                    <td>{{ $row->phone }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-center text-muted">Henüz kısa başvuru yok.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $callbacks->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@extends('seller.master_layout')
@section('title')<title>Admin'e Mesaj</title>@endsection
@section('seller-content')
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>Admin'e Mesaj Gönder</h1></div>
    <div class="section-body">
      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Yeni Mesaj</h4></div>
            <div class="card-body">
              <form action="{{ route('seller.send-admin-message') }}" method="POST">
                @csrf
                <div class="form-group">
                  <label>Konu <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="subject" placeholder="Mesaj konusu" required>
                </div>
                <div class="form-group">
                  <label>Mesaj <span class="text-danger">*</span></label>
                  <textarea name="message" class="form-control" rows="6" placeholder="Mesajınızı yazın..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Gönder</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Gönderilen Mesajlar</h4></div>
            <div class="card-body">
              @forelse($messages as $msg)
                <div class="border-bottom pb-3 mb-3">
                  <div class="d-flex justify-content-between">
                    <strong>{{ $msg->subject }}</strong>
                    <small class="text-muted">{{ $msg->created_at->format('d.m.Y H:i') }}</small>
                  </div>
                  <p class="text-sm text-muted mt-1 mb-0">{{ Str::limit($msg->message, 150) }}</p>
                </div>
              @empty
                <p class="text-muted text-center py-4">Henüz mesaj göndermediniz.</p>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

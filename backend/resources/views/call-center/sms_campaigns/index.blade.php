@extends('call-center.layout.master')

@section('title')
<title>SMS Kampanyaları</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>SMS Kampanyaları</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item active">SMS Kampanyaları</div>
            </div>
        </div>

        <div class="section-body">
            <a href="{{ route('call-center.sms-campaigns.create') }}" class="btn btn-primary mb-3">
                <i class="fas fa-plus"></i> Yeni SMS Gönder
            </a>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Başlık</th>
                                    <th>Segment</th>
                                    <th>Alıcı</th>
                                    <th>Başarılı</th>
                                    <th>Başarısız</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>Detay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $c)
                                <tr>
                                    <td>{{ $c->id }}</td>
                                    <td>{{ $c->title }}</td>
                                    <td>{{ $c->segment }}</td>
                                    <td>{{ $c->total_recipients }}</td>
                                    <td><span class="badge badge-success">{{ $c->sent_count }}</span></td>
                                    <td><span class="badge badge-danger">{{ $c->failed_count }}</span></td>
                                    <td>
                                        @if($c->status === 'completed')
                                            <span class="badge badge-success">Tamamlandı</span>
                                        @elseif($c->status === 'sending')
                                            <span class="badge badge-warning">Gönderiliyor</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $c->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->sent_at ? $c->sent_at->format('d.m.Y H:i') : '-' }}</td>
                                    <td><a href="{{ route('call-center.sms-campaigns.show', $c->id) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center">Henüz SMS kampanyası yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $campaigns->links() }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

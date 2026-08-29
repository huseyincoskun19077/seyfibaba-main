@extends('call-center.layout.master')

@section('title')
<title>SMS Detay - {{ $campaign->title }}</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>SMS Detay</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item"><a href="{{ route('call-center.sms-campaigns.index') }}">SMS Kampanyaları</a></div>
                <div class="breadcrumb-item active">{{ $campaign->title }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <table class="table">
                                <tr><th width="200">Başlık</th><td>{{ $campaign->title }}</td></tr>
                                <tr><th>Segment</th><td>{{ $campaign->segment }}</td></tr>
                                <tr><th>Toplam Alıcı</th><td>{{ $campaign->total_recipients }}</td></tr>
                                <tr><th>Başarılı</th><td><span class="badge badge-success">{{ $campaign->sent_count }}</span></td></tr>
                                <tr><th>Başarısız</th><td><span class="badge badge-danger">{{ $campaign->failed_count }}</span></td></tr>
                                <tr><th>Durum</th><td>{{ $campaign->status }}</td></tr>
                                <tr><th>Gönderim Tarihi</th><td>{{ $campaign->sent_at ? $campaign->sent_at->format('d.m.Y H:i') : '-' }}</td></tr>
                                <tr><th>Mesaj</th><td><pre class="mb-0" style="white-space: pre-wrap;">{{ $campaign->message }}</pre></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

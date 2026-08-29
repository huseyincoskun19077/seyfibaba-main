@extends('admin.master_layout')

@section('title')
<title>SMS Mesaj Şablonları</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>SMS Mesaj Şablonları</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
                <div class="breadcrumb-item"><a href="{{ route('admin.sms-campaigns.index') }}">SMS Kampanyaları</a></div>
                <div class="breadcrumb-item active">Mesaj Şablonları</div>
            </div>
        </div>

        <div class="section-body">
            <a href="{{ route('admin.sms-campaigns.messages.create') }}" class="btn btn-primary mb-3">
                <i class="fas fa-plus"></i> Yeni Mesaj Şablonu
            </a>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Başlık</th>
                                    <th>Mesaj</th>
                                    <th>Karakter</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($messages as $msg)
                                <tr>
                                    <td>{{ $msg->id }}</td>
                                    <td>{{ $msg->title }}</td>
                                    <td><small>{{ Str::limit($msg->message, 80) }}</small></td>
                                    <td><span class="badge badge-info">{{ $msg->char_count }}</span></td>
                                    <td>
                                        @if($msg->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Pasif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.sms-campaigns.messages.edit', $msg->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('admin.sms-campaigns.messages.delete', $msg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center">Henüz mesaj şablonu yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@extends('admin.master_layout')
@section('title')
<title>Üst Bar (Topbar)</title>
@endsection
@section('admin-content')
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>Üst Bar (Topbar)</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">Üst Bar</div>
            </div>
          </div>

            <div class="section-body">
                <div class="row mt-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('admin.update-topbar-contact') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="">Üst bar sloganları</label>
                                        <textarea name="topbar_announcement" class="form-control" rows="6" placeholder="Her satıra bir slogan yazın">{{ old('topbar_announcement', $slogansText) }}</textarea>
                                        <small class="text-muted">
                                            Her satıra <strong>bir slogan</strong> yazın. Sitede üst barda sırayla döner (ör. kargo, kampanya, güvenli ödeme).
                                            Boş bırakırsanız duyuru gizlenir.
                                        </small>
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{__('admin.Topbar Phone')}}</label>
                                        <input type="text" name="topbar_phone" class="form-control" value="{{ old('topbar_phone', $setting->topbar_phone ?? '') }}">
                                        <small class="text-muted">Müşteri hizmetleri numarası olarak üst barda gösterilir.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{__('admin.Topbar Email')}}</label>
                                        <input type="text" name="topbar_email" class="form-control" value="{{ old('topbar_email', $setting->topbar_email ?? '') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{__('admin.Menu Phone')}}</label>
                                        <input type="text" name="menu_phone" class="form-control" value="{{ old('menu_phone', $setting->menu_phone ?? '') }}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{__('admin.Update')}}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
      </div>
@endsection

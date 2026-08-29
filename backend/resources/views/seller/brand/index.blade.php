@extends('seller.master_layout')
@section('title')
<title>Markalar</title>
@endsection
@section('seller-content')
<!-- Main Content -->
<div class="main-content">
    <section class="section">
      <div class="section-header">
        <h1>Markalar</h1>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
          <div class="breadcrumb-item">Markalar</div>
        </div>
      </div>
      <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Marka Ekle</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('seller.brand.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Marka Adı</label>
                                        <input type="text" name="name" class="form-control" placeholder="Marka adını girin" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Logo</label>
                                        <input type="file" name="logo" class="form-control" accept="image/*" required>
                                        <small class="text-muted">Önerilen: 100x100 piksel</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Marka Ekle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Admin Markaları</h5>
                    </div>
                    <div class="card-body">
                        @if($brands->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Ad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($brands as $brand)
                                        <tr>
                                            <td><img src="{{ asset($brand->logo) }}" width="40"></td>
                                            <td>{{ $brand->name }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Henüz admin markası yok.</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Benim Markalarım</h5>
                    </div>
                    <div class="card-body">
                        @if($myBrands->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Ad</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($myBrands as $brand)
                                        <tr>
                                            <td><img src="{{ asset($brand->logo) }}" width="40"></td>
                                            <td>{{ $brand->name }}</td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal{{ $brand->id }}">Düzenle</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Henüz kendi markanızı eklemediniz.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
      </div>
    </section>
</div>

<!-- Edit Modals -->
@foreach($myBrands as $brand)
<div class="modal fade" id="editModal{{ $brand->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('seller.brand.update', $brand->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Marka Düzenle</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Marka Adı</label>
                        <input type="text" name="name" class="form-control" value="{{ $brand->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Logo (Değiştirmek istemiyorsanız boş bırakın)</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if($brand->logo)
                        <img src="{{ asset($brand->logo) }}" width="50" class="mt-2">
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Product gallery')}}</title>
@endsection
@section('seller-content')
<div class="main-content seller-product-form">
  <section class="section">
    <div class="section-header">
      <h1>{{__('admin.Product gallery')}}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('seller.product.index') }}">{{__('admin.Product')}}</a></div>
        <div class="breadcrumb-item">{{__('admin.Product gallery')}}</div>
      </div>
    </div>

    <div class="section-body">
      <a href="{{ route('seller.product.index') }}" class="btn btn-primary mb-3"><i class="fas fa-list"></i> {{__('admin.Products')}}</a>

      <div class="row mt-2">
        <div class="col">
          <div class="card">
            <div class="card-header">
              <h4 class="mb-0">{{__('admin.Product')}} : {{ $product->name }}</h4>
            </div>
            <div class="card-body">
              <form action="{{ route('seller.store-product-gallery') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('seller.partials.photo_picker', [
                  'inputName' => 'images[]',
                  'inputId' => 'gallery_page_images',
                  'multiple' => true,
                  'required' => true,
                  'label' => 'Galeri görselleri',
                ])
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn btn-primary btn-lg btn-block mt-3">Yükle</button>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="row">
                @forelse($gallery as $item)
                  <div class="col-6 col-md-3 mb-3">
                    <div class="card shadow-sm">
                      <img src="{{ asset($item->image) }}" class="card-img-top" alt="" style="height:140px;object-fit:cover;">
                      <div class="card-body p-2 text-center">
                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $item->id }})">
                          <i class="fa fa-trash"></i> Sil
                        </a>
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="col-12">
                    <p class="text-center text-muted py-4">Henüz galeri görseli yok.</p>
                  </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  function deleteData(id){
    $("#deleteForm").attr("action",'{{ url("seller/delete-product-image") }}'+"/"+id)
  }
</script>
@endsection

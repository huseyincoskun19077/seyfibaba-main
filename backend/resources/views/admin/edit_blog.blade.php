@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Blog')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Edit Blog')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.blog.index') }}">{{__('admin.Blog')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Edit Blog')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex align-items-center mb-4">
              <a href="{{ route('admin.blog.index') }}" class="btn btn-primary mr-2"><i class="fas fa-list"></i> {{__('admin.Blog')}}</a>
              @include('admin.partials.ai_content_generator_button')
            </div>

            <form action="{{ route('admin.blog.update',$blog->id) }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')

              {{-- Tab Navigation --}}
              <ul class="nav nav-tabs" id="blogTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="content-tab" data-toggle="tab" href="#tab-content" role="tab">
                    <i class="fas fa-edit mr-1"></i> İçerik
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="images-tab" data-toggle="tab" href="#tab-images" role="tab">
                    <i class="fas fa-images mr-1"></i> Görseller
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="seo-tab" data-toggle="tab" href="#tab-seo" role="tab">
                    <i class="fas fa-search mr-1"></i> SEO
                  </a>
                </li>
              </ul>

              {{-- Tab Content --}}
              <div class="tab-content" id="blogTabContent">

                {{-- TAB: İçerik --}}
                <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>{{__('admin.Title')}} <span class="text-danger">*</span></label>
                            <input type="text" id="title" class="form-control" name="title" value="{{ $blog->title }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Slug')}} <span class="text-danger">*</span></label>
                            <input type="text" id="slug" class="form-control" name="slug" value="{{ $blog->slug }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Category')}} <span class="text-danger">*</span></label>
                            <select name="category" class="form-control select2" id="category">
                                <option value="">{{__('admin.Select Category')}}</option>
                                @foreach ($categories as $category)
                                    <option {{ $category->id == $blog->blog_category_id ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>{{__('admin.Show Homepage ?')}}</label>
                            <select name="show_homepage" class="form-control">
                                <option {{ $blog->show_homepage == 0 ? 'selected' : '' }} value="0">{{__('admin.No')}}</option>
                                <option {{ $blog->show_homepage == 1 ? 'selected' : '' }} value="1">{{__('admin.Yes')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option {{ $blog->status == 1 ? 'selected' : '' }} value="1">{{__('admin.Active')}}</option>
                                <option {{ $blog->status == 0 ? 'selected' : '' }} value="0">{{__('admin.Inactive')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Description')}} <span class="text-danger">*</span></label>
                            <textarea name="description" cols="30" rows="10" class="summernote">{{ $blog->description }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: Görseller --}}
                <div class="tab-pane fade" id="tab-images" role="tabpanel">
                  {{-- Ana Görsel --}}
                  <div class="card">
                    <div class="card-header"><h4>Ana Görsel</h4></div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-md-3">
                            <img id="preview-img" class="admin-img img-fluid rounded" src="{{ asset($blog->image) }}" alt="">
                        </div>
                        <div class="form-group col-md-9 d-flex align-items-center">
                            <input type="file" class="form-control-file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewThumnailImage(event)">
                            <small class="form-text text-muted mt-2">JPG, PNG, WEBP — en fazla 8 MB. HEIC/iPhone fotoğrafı çalışmaz, önce JPG’ye çevirin.</small>
                        </div>
                      </div>
                    </div>
                  </div>

                  {{-- Blog Galerisi --}}
                  <div class="card">
                    <div class="card-header"><h4>Blog Galerisi</h4></div>
                    <div class="card-body">
                      <div class="form-group">
                          <label>Yeni Görsel Ekle (Çoklu seçim yapabilirsiniz)</label>
                          <input type="file" class="form-control-file" id="gallery-input" multiple accept="image/*">
                          <button type="button" class="btn btn-success mt-2" id="upload-gallery-btn">
                              <i class="fas fa-upload mr-1"></i> Yükle
                          </button>
                      </div>
                      <hr>
                      <div class="row" id="gallery-container">
                          @if($blog->gallery && $blog->gallery->count() > 0)
                              @foreach($blog->gallery as $image)
                                  <div class="col-md-3 col-sm-4 mb-3" id="gallery-item-{{ $image->id }}">
                                      <div class="card shadow-sm">
                                          <img src="{{ asset($image->image) }}" class="card-img-top" alt="" style="height:150px; object-fit:cover;">
                                          <div class="card-body p-2 text-center">
                                              <button type="button" class="btn btn-danger btn-sm delete-gallery-btn" data-id="{{ $image->id }}">
                                                  <i class="fas fa-trash"></i> Sil
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                              @endforeach
                          @endif
                      </div>
                      <div id="gallery-empty" class="{{ ($blog->gallery && $blog->gallery->count() > 0) ? 'd-none' : '' }}">
                          <p class="text-muted text-center py-4"><i class="fas fa-images mr-1"></i> Henüz galeri görseli eklenmemiş.</p>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: SEO --}}
                <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>{{__('admin.SEO Title')}}</label>
                            <input type="text" class="form-control" name="seo_title" value="{{ $blog->seo_title }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.SEO Description')}}</label>
                            <textarea name="seo_description" cols="30" rows="10" class="form-control text-area-5">{{ $blog->seo_description }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>{{-- /tab-content --}}

              <div class="row mt-3">
                  <div class="col-12">
                      <button class="btn btn-primary">{{__('admin.Update')}}</button>
                  </div>
              </div>
            </form>
          </div>
        </section>
      </div>

@include('admin.partials.ai_content_generator_modal')

<script>
    (function($) {
        "use strict";
        $(document).ready(function () {
            $("#title").on("focusout",function(e){
                $("#slug").val(convertToSlug($(this).val()));
            })
        });
    })(jQuery);

    function previewThumnailImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('preview-img');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    };

    // Galeri yükleme
    $("#upload-gallery-btn").on("click", function() {
        var files = $("#gallery-input")[0].files;
        if (files.length === 0) {
            toastr.warning("Lütfen en az bir görsel seçin.");
            return;
        }
        var formData = new FormData();
        formData.append("_token", "{{ csrf_token() }}");
        formData.append("blog_id", "{{ $blog->id }}");
        for (var i = 0; i < files.length; i++) {
            formData.append("images[]", files[i]);
        }
        var btn = $(this);
        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Yükleniyor...');
        $.ajax({
            type: "POST",
            url: "{{ route('admin.store-blog-gallery') }}",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Yükle');
                $("#gallery-input").val("");
                toastr.success("Görseller başarıyla yüklendi.");
                location.reload();
            },
            error: function(xhr) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Yükle');
                toastr.error("Yükleme hatası.");
            }
        });
    });

    // Galeri görsel silme
    $(document).on("click", ".delete-gallery-btn", function() {
        var id = $(this).data("id");
        if (!confirm("Bu görseli silmek istediğinize emin misiniz?")) return;
        $.ajax({
            type: "POST",
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { _token: "{{ csrf_token() }}", _method: "DELETE" },
            url: "{{ url('/admin/delete-blog-image') }}/" + id,
            success: function(response) {
                $("#gallery-item-" + id).fadeOut(300, function() {
                    $(this).remove();
                    if ($("#gallery-container").children().length === 0) {
                        $("#gallery-empty").removeClass("d-none");
                    }
                });
                toastr.success("Görsel silindi.");
            },
            error: function(err) {
                toastr.error("Silme hatası.");
            }
        });
    });
</script>
@endsection

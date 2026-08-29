@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Blog')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Create Blog')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.blog.index') }}">{{__('admin.Blog')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Create Blog')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex align-items-center mb-4">
              <a href="{{ route('admin.blog.index') }}" class="btn btn-primary mr-2"><i class="fas fa-list"></i> {{__('admin.Blog')}}</a>
              @include('admin.partials.ai_content_generator_button')
            </div>

            <form action="{{ route('admin.blog.store') }}" method="POST" enctype="multipart/form-data">
              @csrf

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
                            <input type="text" id="title" class="form-control" name="title" value="{{ old('title') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Slug')}} <span class="text-danger">*</span></label>
                            <input type="text" id="slug" class="form-control" name="slug" value="{{ old('slug') }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Category')}} <span class="text-danger">*</span></label>
                            <select name="category" class="form-control select2" id="category">
                                <option value="">{{__('admin.Select Category')}}</option>
                                @foreach ($categories as $category)
                                    <option {{ $category->id == old('category') ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>{{__('admin.Show Homepage ?')}}</label>
                            <select name="show_homepage" class="form-control">
                                <option value="0">{{__('admin.No')}}</option>
                                <option value="1">{{__('admin.Yes')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option value="1">{{__('admin.Active')}}</option>
                                <option value="0">{{__('admin.Inactive')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Description')}} <span class="text-danger">*</span></label>
                            <textarea name="description" cols="30" rows="10" class="summernote">{{ old('description') }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: Görseller --}}
                <div class="tab-pane fade" id="tab-images" role="tabpanel">
                  <div class="card">
                    <div class="card-header"><h4>Ana Görsel</h4></div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-md-3">
                            <img id="preview-img" class="admin-img img-fluid rounded" src="{{ asset('uploads/website-images/preview.png') }}" alt="">
                        </div>
                        <div class="form-group col-md-9 d-flex align-items-center">
                            <input type="file" class="form-control-file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewThumnailImage(event)">
                            <small class="form-text text-muted mt-2">JPG, PNG, WEBP — en fazla 8 MB.</small>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-header"><h4>Blog Galerisi</h4></div>
                    <div class="card-body">
                      <div class="alert alert-info">
                          <i class="fas fa-info-circle mr-1"></i> Blog yazısını kaydettikten sonra galeri görselleri ekleyebilirsiniz.
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
                            <input type="text" class="form-control" name="seo_title" value="{{ old('seo_title') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.SEO Description')}}</label>
                            <textarea name="seo_description" cols="30" rows="10" class="form-control text-area-5">{{ old('seo_description') }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>{{-- /tab-content --}}

              <div class="row mt-3">
                  <div class="col-12">
                      <button class="btn btn-primary">{{__('admin.Save')}}</button>
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
</script>
@endsection

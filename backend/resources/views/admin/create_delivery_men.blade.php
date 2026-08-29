@extends('admin.master_layout')
@section('title')
<title>Teslimat Görevlisi Ekle</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>Yeni Teslimat Görevlisi Ekle</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.delivery-man.index') }}">Teslimat Görevlileri</a></div>
              <div class="breadcrumb-item">Oluştur</div>
            </div>
          </div>

        <div class="section-body">
            <a href="{{ route('admin.delivery-man.index') }}" class="btn btn-primary"><i class="fas fa-list"></i> Teslimat Görevlileri</a>
            <div class="row mt-4">
                <div class="col-12">
                    <form action="{{ route('admin.delivery-man.store') }}" method="POST" enctype="multipart/form-data">
                        <div class="card">
                            <div class="card-body">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label>Teslimat Görevlisi Görseli <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control-file" name="man_image">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Ad <span class="text-danger">*</span></label>
                                        <input type="text" id="fname" class="form-control"  name="fname" value="{{ old('fname') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Soyad <span class="text-danger">*</span></label>
                                        <input type="text" id="lname" class="form-control"  name="lname" value="{{ old('lname') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>E-posta <span class="text-danger">*</span></label>
                                        <input type="text" id="email" class="form-control"  name="email" value="{{ old('email') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Teslimat Görevlisi Türü <span class="text-danger">*</span></label>
                                        <select class="form-control" name="man_type" id="man_type">
                                            <option value="freelancer">Serbest Çalışan</option>
                                            <option value="salary based">Maaşlı</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Kimlik Türü <span class="text-danger">*</span></label>
                                        <select class="form-control" name="idn_type" id="idn_type">
                                            <option value="passport">Pasaport</option>
                                            <option value="driving license">Sürücü Belgesi</option>
                                            <option value="nid">Kimlik Kartı</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Kimlik Numarası <span class="text-danger">*</span></label>
                                        <input type="text" id="idn_num" class="form-control"  name="idn_num" value="{{ old('idn_num') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Kimlik Görseli <span class="text-danger">*</span></label>
                                        <input type="file" id="idn_image" class="form-control-file"  name="idn_image">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Telefon <span class="text-danger">*</span></label>
                                        <input type="text" id="phone" class="form-control"  name="phone" value="{{ old('phone') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Şifre <span class="text-danger">*</span></label>
                                        <input type="text" id="password" class="form-control"  name="password" value="{{ old('password') }}">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Şifre Tekrarı <span class="text-danger">*</span></label>
                                        <input type="text" id="c_password" class="form-control"  name="c_password" value="{{ old('c_password') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button class="btn btn-primary">{{__('admin.Save')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

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

    function previewBannerImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('preview-banner-img');
            output.src = reader.result;
        }

        reader.readAsDataURL(event.target.files[0]);
    };

</script>
@endsection

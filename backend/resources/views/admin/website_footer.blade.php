@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Footer')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Footer')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Footer')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="row mt-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.footer.update', $footer->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>{{__('admin.About Us')}} <span class="text-danger">*</span></label>
                                    <textarea name="about_us" id="" cols="30" rows="10" class="form-control text-area-5">{{ $footer->about_us }}</textarea>
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Email')}} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="{{ $footer->email }}">
                                </div>



                                <div class="form-group col-12">
                                    <label>{{__('admin.Phone')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="{{ $footer->phone }}">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Address')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="address" class="form-control" value="{{ $footer->address }}">
                                </div>
                                <div class="form-group col-12">
                                    <label>{{__('admin.Existing Image')}} (Ödeme kartları)</label>
                                    <div>
                                        @if($footer->payment_image)
                                          <img src="{{ asset($footer->payment_image) }}" alt="" width="220px">
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group col-12">
                                    <label>{{__('admin.Payment Card Image')}}</label>
                                    <input type="file" name="card_image" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
                                </div>

                                <div class="form-group col-md-6">
                                    <label>ETBİS görseli</label>
                                    @if(!empty($footer->etbis_image))
                                      <div class="mb-2"><img src="{{ asset($footer->etbis_image) }}" alt="ETBİS" style="max-height:80px"></div>
                                    @endif
                                    <input type="file" name="etbis_image" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>ETBİS URL</label>
                                    <input type="url" name="etbis_url" class="form-control" value="{{ $footer->etbis_url ?? '' }}" placeholder="https://www.eticaret.gov.tr/...">
                                </div>

                                <div class="form-group col-md-6">
                                    <label>App Store logosu</label>
                                    @if(!empty($footer->app_store_image))
                                      <div class="mb-2"><img src="{{ asset($footer->app_store_image) }}" alt="App Store" style="max-height:48px"></div>
                                    @endif
                                    <input type="file" name="app_store_image" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>App Store URL</label>
                                    <input type="url" name="app_store_url" class="form-control" value="{{ $footer->app_store_url ?? '' }}" placeholder="https://apps.apple.com/...">
                                </div>

                                <div class="form-group col-md-6">
                                    <label>Google Play logosu</label>
                                    @if(!empty($footer->play_store_image))
                                      <div class="mb-2"><img src="{{ asset($footer->play_store_image) }}" alt="Google Play" style="max-height:48px"></div>
                                    @endif
                                    <input type="file" name="play_store_image" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Google Play URL</label>
                                    <input type="url" name="play_store_url" class="form-control" value="{{ $footer->play_store_url ?? '' }}" placeholder="https://play.google.com/...">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.First Column Title')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="first_column" class="form-control" value="{{ $footer->first_column }}">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Second Column Title')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="second_column" class="form-control" value="{{ $footer->second_column }}">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Third Column Title')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="third_column" class="form-control" value="{{ $footer->third_column }}">
                                </div>





                                <div class="form-group col-12">
                                    <label>{{__('admin.Copyright')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="copyright" class="form-control" value="{{ $footer->copyright }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-primary">{{__('admin.Update')}}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                  </div>
                </div>
          </div>
        </section>
      </div>
@endsection

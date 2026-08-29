@extends('seller.master_layout')
@section('title')
<title>{{__('admin.My withdraw')}}</title>
@endsection
@section('seller-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.My withdraw')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.My withdraw')}}</div>
            </div>
          </div>

          <div class="section-body">
            <a href="{{ route('seller.my-withdraw.index') }}" class="btn btn-primary"><i class="fas fa-list"></i> {{__('admin.My withdraw')}}</a>

            <div class="row mt-3">
              <div class="col-12">
                <div class="card">
                  <div class="card-body">
                    <h6 class="text-primary mb-3">{{ __('admin.Earnings breakdown') }}</h6>
                    <div class="row">
                      <div class="col-md-3 col-6 mb-2"><strong>{{ __('admin.Withdrawable balance') }}</strong><br>
                        <span class="text-success">{{ $setting->currency_icon }}{{ number_format($earnings['withdrawable_balance'], 2, ',', '.') }}</span></div>
                      <div class="col-md-3 col-6 mb-2"><strong>{{ __('admin.Net from completed orders') }}</strong><br>
                        {{ $setting->currency_icon }}{{ number_format($earnings['settled_net'], 2, ',', '.') }}</div>
                      <div class="col-md-3 col-6 mb-2"><strong>{{ __('admin.Approved withdrawals total') }}</strong><br>
                        {{ $setting->currency_icon }}{{ number_format($earnings['approved_withdraw_total'], 2, ',', '.') }}</div>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0">
                      <small class="text-muted">{!! __('admin.Earnings help marketplace') !!}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-4">
                <div class="col-6">
                  <div class="card">
                    <div class="card-body">
                        <form action="{{ route('seller.my-withdraw.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="">{{__('admin.Withdraw Method')}}</label>
                            <select name="method_id" id="method_id" class="form-control">
                                <option value="">{{__('admin.Select Method')}}</option>
                                @foreach ($methods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="">{{__('admin.Withdraw Amount')}}</label>
                            <input type="number" step="0.01" min="0.01" @if($earnings['withdrawable_balance'] > 0) max="{{ $earnings['withdrawable_balance'] }}" @endif class="form-control" name="withdraw_amount" placeholder="{{ __('admin.Max') }}: {{ number_format($earnings['withdrawable_balance'], 2, ',', '.') }}">
                            <small class="form-text text-muted">{{ __('admin.Request cannot exceed withdrawable balance') }}</small>
                        </div>


                        <div class="form-group">
                            <label for="">{{__('admin.Account Information')}}</label>
                            <textarea name="account_info" id="" cols="30" rows="10" class="form-control text-area-5"></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit">{{__('admin.Send Request')}}</button>
                        </form>
                    </div>
                  </div>
                </div>

                <div class="col-6 d-none" id="method_des_box">
                    <div class="card">
                        <div class="card-body" id="method_des">

                        </div>
                    </div>
                </div>
          </div>
        </section>
      </div>



<script>
    (function($) {
    "use strict";
    $(document).ready(function () {
        $("#method_id").on('change', function(){
            var methodId = $(this).val();
            $.ajax({
                type:"get",
                url:"{{url('/seller/get-withdraw-account-info/')}}"+"/"+methodId,
                success:function(response){
                   $("#method_des").html(response)
                   $("#method_des_box").removeClass('d-none')
                },
                error:function(err){}
            })

            if(!methodId){
                $("#method_des_box").addClass('d-none')
            }

        })
    });

    })(jQuery);
</script>
@endsection

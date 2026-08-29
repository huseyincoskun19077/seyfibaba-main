@extends('layout')
@section('title')
    <title>{{__('Payment')}}</title>
@endsection
@section('meta')
    <meta name="description" content="{{__('payment')}}">
@endsection

@section('public-content')


    <!--============================
         BREADCRUMB START
    ==============================-->
    <section id="wsus__breadcrumb" style="background: url({{  asset($banner->image) }});">
        <div class="wsus_breadcrumb_overlay">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h4>{{__('payment')}}</h4>
                        <ul>
                            <li><a href="{{ route('home') }}">{{__('home')}}</a></li>
                            <li><a href="{{ route('user.checkout.payment') }}">{{__('payment')}}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
        BREADCRUMB END
    ==============================-->

        <!--============================
        PAYMENT PAGE START
    ==============================-->
    <section id="wsus__cart_view">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul class="wsus__cart_tab">

                        <li><a href="{{ route('cart') }}">{{__('Shopping Cart')}}</a></li>
                        <li><a href="{{ route('user.checkout.billing-address') }}">{{__('Billing Address')}}</a></li>
                        <li><a href="{{ route('user.checkout.checkout') }}">{{__('Shipping Address')}}</a></li>
                        <li><a class="wsus__order_active" href="{{ route('user.checkout.payment') }}">{{__('payment')}}</a></li>

                    </ul>
                </div>
            </div>

            @php
                $subTotal = 0;
                foreach ($cartContents as $cartContent) {
                    $variantPrice = 0;
                    foreach ($cartContent->options->variants as $indx => $variant) {
                        $variantPrice += $cartContent->options->prices[$indx];
                    }
                    $productPrice = $cartContent->price;
                    $total = $productPrice * $cartContent->qty ;
                    $subTotal += $total;
                }

                $tax_amount = 0;
                $total_price = 0;
                $coupon_price = 0;
                foreach ($cartContents as $key => $content) {
                    $tax = $content->options->tax * $content->qty;
                    $tax_amount = $tax_amount + $tax;
                }

                $total_price = $tax_amount + $subTotal;

                if(Session::get('coupon_price') && Session::get('offer_type')) {
                    if(Session::get('offer_type') == 1) {
                        $coupon_price = Session::get('coupon_price');
                        $coupon_price = ($coupon_price / 100) * $total_price;
                    }else {
                        $coupon_price = Session::get('coupon_price');
                    }
                }

                $total_price = $total_price - $coupon_price ;
                $total_price += $shipping_fee;
            @endphp
            <div class="wsus__pay_info_area">
                <div class="row">
                    <div class="col-xl-2 col-lg-2">
                        <div class="wsus__payment_menu">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                @if ($bankPayment->status == 1)
                                <button class="nav-link common_btn active" id="v-bank-payment-tab" data-bs-toggle="pill" data-bs-target="#v-bank-payment" type="button" role="tab" aria-controls="v-bank-payment" aria-selected="true">{{__('Bank')}}</button>
                                @endif

                                @if ($bankPayment->cash_on_delivery_status == 1)
                                <button class="nav-link common_btn {{ $bankPayment->status != 1 ? 'active' : '' }}" id="v-pills-settings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="{{ $bankPayment->status != 1 ? 'true' : 'false' }}">{{__('Cash on delivery')}}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade {{ $bankPayment->status == 1 ? 'show active' : '' }}" id="v-bank-payment" role="tabpanel" aria-labelledby="v-bank-payment-tab">

                                {!! nl2br(e($bankPayment->account_info)) !!}

                                <form class="wsus__input_area mt-3" action="{{ route('user.checkout.pay-with-bank') }}" method="POST">
                                    @csrf
                                    <textarea cols="3" rows="2" name="tnx_info" placeholder="{{__('Payment Information')}}" required></textarea>
                                    <button type="submit" class="common_btn mt-4">{{__('Submit Order')}}</button>
                                </form>
                            </div>

                            <div class="tab-pane fade {{ $bankPayment->status != 1 ? 'show active' : '' }}" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                <form class="wsus__input_area" action="{{ route('user.checkout.cash-on-delivery') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="common_btn mt-4">{{__('Submit Order')}}</button>
                                </form>
                            </div>
                          </div>
                    </div>


                    <div class="col-xl-4 col-lg-4">
                        <div class="wsus__pay_booking_summary" id="sticky_sidebar2">
                            <h5>{{__('Order Summary')}}</h5>
                            <p>{{__('subtotal')}}: <span>{{ $setting->currency_icon }}{{ $subTotal }}</span></p>
                            <p>{{__('shipping fee')}}(+): <span>{{ $setting->currency_icon }}{{ $shipping_fee }} </span></p>
                            <p>{{__('Tax')}}(+): <span>{{ $setting->currency_icon }}{{ $tax_amount }}</span></p>
                            <p>{{__('Coupon')}}(-): <span>{{ $setting->currency_icon }}{{  $coupon_price  }}</span></p>
                            <h6>{{__('total')}} <span>{{ $setting->currency_icon }}{{ $total_price }}</span></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
        PAYMENT PAGE END
    ==============================-->

@endsection

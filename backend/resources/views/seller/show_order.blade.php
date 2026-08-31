@extends('seller.master_layout')
@section('title')
<title>{{ __('admin.Invoice') }}</title>
@endsection
<style>
    @media print {
        .section-header,
        .order-status,
        #sidebar-wrapper,
        .print-area,
        .main-footer,
        .new-product,
        .mainas,
        .plus,
        .action-btn,
        .delete-icon,
        .new-product,
        .additional_info {
            display: none !important;
        }
    }
</style>
@section('seller-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('admin.Invoice') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{ __('admin.Dashboard') }}</a></div>
                    <div class="breadcrumb-item">{{ __('admin.Invoice') }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="invoice">
                    <div class="invoice-print">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="invoice-title">
                                    <h2><img src="{{ asset($setting->logo) }}" alt="" width="120px"></h2>
                                    <div class="invoice-number">Order #{{ $order->order_id }}</div>
                                </div>
                                <hr>
                                @include('partials.seller_order_flow', ['order' => $order])
                                @php
                                    $orderAddress = $order->orderAddress;
                                @endphp
                                @include('partials.order_invoice.addresses', ['orderAddress' => $orderAddress])
                                @include('partials.order_invoice.payment_block', ['order' => $order, 'setting' => $setting, 'invoiceContext' => 'seller'])
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="section-title">{{ __('admin.Order Summary') }}</div>
                                @include('partials.order_invoice.summary_table', [
                                    'order' => $order,
                                    'setting' => $setting,
                                    'invoiceContext' => 'seller',
                                ])
                                @include('partials.order_invoice.additional_info', ['order' => $order])

                                @include('partials.order_invoice.order_status_and_totals_row', [
                                    'order' => $order,
                                    'setting' => $setting,
                                    'invoiceContext' => 'seller',
                                    'orderDistinctSellerCount' => $orderDistinctSellerCount,
                                    'sellerLinesSubtotal' => $sellerLinesSubtotal,
                                ])

                                @if(config('features.geliver_enabled') && \App\Support\SellerOrderFlow::sellerStatus($order->orderProducts) === 1)
                                @include('partials.order_geliver_card', ['order' => $order, 'cardId' => 'geliver-kargo'])
                                @endif

                                {{-- Manuel kargo ve aksiyonlar artık seller_order_flow partial içinde sıralı gösteriliyor --}}
                            </div>
                        </div>
                    </div>

                    <div class="text-md-right print-area">
                        <hr>
                        <button class="btn btn-success btn-icon icon-left print_btn"><i class="fas fa-print"></i> {{ __('admin.Print') }}</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @if ($setting->map_status == 1)
        <div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mapModalLabel">{{ $order->orderAddress?->shipping_address }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="map" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($setting->map_status == 1)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $setting->map_key }}&callback=initMap" async defer></script>

        <script>
            let map;

            function initMap() {
                const location = {
                    lat: {{ $order->latitude ?? 0 }},
                    lng: {{ $order->longitude ?? 0 }}
                };
                map = new google.maps.Map(document.getElementById("map"), {
                    center: location,
                    zoom: 10
                });
                new google.maps.Marker({
                    position: location,
                    map: map
                });
            }

            $('#mapModal').on('shown.bs.modal', function() {
                google.maps.event.trigger(map, "resize");
                map.setCenter({
                    lat: {{ $order->latitude ?? 0 }},
                    lng: {{ $order->longitude ?? 0 }}
                });
            });

            $('#viewMapButton').click(function() {
                const lat = {{ $order->latitude ?? 0 }};
                const lng = {{ $order->longitude ?? 0 }};
                const location = {
                    lat: lat,
                    lng: lng
                };
                map.setCenter(location);
                new google.maps.Marker({
                    position: location,
                    map: map
                });
            });
        </script>
    @endif

    <script>
        (function($) {
            "use strict";
            $(document).ready(function() {
                $(".print_btn").on("click", function() {
                    $(".custom_click").click();
                    window.print()
                })
            });
        })(jQuery);
    </script>

    <script>
        @if(config('features.geliver_enabled'))
        @include('partials.order_geliver_scripts', ['cargoBaseUrl' => url('seller/orders'), 'order' => $order])
        @endif
    </script>
@endsection

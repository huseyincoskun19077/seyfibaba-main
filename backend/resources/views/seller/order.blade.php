@extends('seller.master_layout')
@section('title')
<title>{{ $title }}</title>
@endsection
@section('seller-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{ $title }}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard') }}</a></div>
              <div class="breadcrumb-item">{{ $title }}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="5%">{{__('admin.SN')}}</th>
                                    <th width="10%">{{__('admin.Customer')}}</th>
                                    <th width="10%">{{__('admin.Order Id')}}</th>
                                    <th width="15%">{{__('admin.Date')}}</th>
                                    <th width="10%">{{__('admin.Quantity')}}</th>
                                    <th width="10%">{{__('admin.Amount')}}</th>
                                    <th width="10%">{{__('admin.Order Status')}}</th>
                                    <th width="10%">Hakediş</th>
                                    <th width="10%">{{__('admin.Payment')}}</th>
                                    <th width="15%">{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $index => $order)
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td>{{ $order->user->name }}</td>
<td>{{ $order->order_id }}</td>
                                        <td>{{ $order->created_at->format('d F, Y') }}</td>
                                        <td>{{ $order->orderProducts->sum('qty') }}</td>
                                        <td>{{ $setting->currency_icon }}{{ round($order->orderProducts->sum(function($op) { return ($op->qty ?? 1) * ($op->unit_price ?? 0); })) }}</td>
                                        <td>
                                            @php
                                                $sellerStatus = \App\Support\SellerOrderFlow::sellerStatus($order->orderProducts);
                                                $payout = \App\Support\SellerOrderFlow::payoutInfo($order, $order->orderProducts);
                                            @endphp
                                            @if ($sellerStatus === 4)
                                                <span class="badge badge-danger">{{__('admin.Declined')}}</span>
                                            @elseif ($sellerStatus === 3)
                                                <span class="badge badge-success">{{__('admin.Completed')}}</span>
                                            @elseif ($sellerStatus === 2)
                                                <span class="badge badge-info">Kargoda</span>
                                            @elseif ($sellerStatus === 1)
                                                <span class="badge badge-warning">Hazırlanıyor</span>
                                            @else
                                                <span class="badge badge-secondary">Yeni Sipariş</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $payout['badge'] }}">{{ $payout['state'] === 'paid' ? 'Ödendi' : ($payout['state'] === 'waiting' ? 'Bekliyor' : 'Beklemede') }}</span>
                                        </td>
                                        <td>
                                            @if($order->payment_status == 1)
                                            <span class="badge badge-success">{{__('admin.success')}} </span>
                                            @else
                                            <span class="badge badge-danger">{{__('admin.Pending')}}</span>
                                            @endif
                                        </td>

                                        <td>
                                        {{-- Sipariş içi: onay, teslim, kargo — sadece detay sayfasında (admin listesi gibi) --}}
                                        <a href="{{ route('seller.order-show',$order->id) }}" class="btn btn-primary btn-sm" title="{{__('admin.View')}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                        </td>
                                    </tr>
                                  @endforeach
                            </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
          </div>
        </section>
      </div>

@endsection

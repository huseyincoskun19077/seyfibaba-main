@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Dashboard')}}</title>
@endsection
@section('seller-content')
<!-- Main Content -->
<div class="main-content">
    <section class="section">
      <div class="section-header">
        <h1>{{__('admin.Dashbaord')}}</h1>
      </div>

      {{-- KYC Durum Uyarısı --}}
      @php
        $kycStatus = $seller->kyc_status ?? 'not_submitted';
      @endphp
      @if($kycStatus !== 'approved')
        <div class="alert alert-{{ $kycStatus === 'rejected' ? 'danger' : ($kycStatus === 'pending' ? 'warning' : 'warning') }} alert-dismissible show fade" style="border-left: 4px solid {{ $kycStatus === 'rejected' ? '#dc3545' : '#ffc107' }}; margin-bottom: 20px;">
          <div class="alert-icon"><i class="fas fa-{{ $kycStatus === 'rejected' ? 'times-circle' : ($kycStatus === 'pending' ? 'clock' : 'exclamation-triangle') }}"></i></div>
          <div class="alert-body">
            <strong>
              @if($kycStatus === 'not_submitted')
                Hesap Doğrulaması Gerekli
              @elseif($kycStatus === 'pending')
                Doğrulama İncelemede
              @elseif($kycStatus === 'rejected')
                Doğrulama Reddedildi
              @endif
            </strong>
            <p style="margin: 4px 0 0;">
              @if($kycStatus === 'not_submitted')
                Ürün ekleyebilmek için <strong>KYC doğrulamanızı</strong> tamamlamanız gerekmektedir. Belge ve IBAN bilgilerinizi yükleyin.
              @elseif($kycStatus === 'pending')
                Belgeleriniz inceleniyor. Onay sonrası ürün ekleyebileceksiniz.
              @elseif($kycStatus === 'rejected')
                Belgeleriniz reddedildi. Lütfen belgeleri yeniden yükleyip tekrar gönderin.
              @endif
              <a href="{{ route('seller.kyc') }}" class="alert-link" style="margin-left: 8px;">Doğrulama Sayfasına Git →</a>
            </p>
          </div>
        </div>
      @endif

      @if($kycStatus === 'approved')
        <div class="card mb-4" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(15,23,42,.08);">
          <div class="card-body p-0">
            <div style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);color:#fff;padding:22px 24px;">
              <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;">
                <div>
                  <h4 class="mb-1" style="font-weight:700;"><i class="fas fa-box-open mr-2"></i>Ürün Yükleme Merkezi</h4>
                  <p class="mb-0" style="opacity:.92;font-size:.95rem;">Ürünlerinizi hızlıca yükleyin — tek tek, toplu Excel veya AI asistan ile</p>
                </div>
                <div class="text-right">
                  <span class="badge badge-light mr-1">{{ $publishedProductCount ?? 0 }} yayında</span>
                  @if(($draftProductCount ?? 0) > 0)
                    <span class="badge badge-warning">{{ $draftProductCount }} taslak</span>
                  @endif
                </div>
              </div>
            </div>
            <div class="p-3 p-md-4">
              <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                  <a href="{{ route('seller.product.quick-create') }}" class="d-block h-100 text-decoration-none p-3" style="background:linear-gradient(135deg,#eef2ff,#f5f3ff);border-radius:16px;border:2px solid #c7d2fe;">
                    <div style="font-size:1.75rem;margin-bottom:8px;">⚡</div>
                    <strong style="color:#4338ca;font-size:1.05rem;">Hızlı Ürün Ekle</strong>
                    <p class="text-muted mb-2 mt-1" style="font-size:.88rem;">Ad, adet, fiyat, fotoğraf — berber/kuaför ürünü için AI açıklama ve kategori doldurur</p>
                    <span class="btn btn-sm btn-primary" style="background:#6366f1;border:none;border-radius:10px;">Başla →</span>
                  </a>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                  <a href="{{ route('seller.product-import-page') }}" class="d-block h-100 text-decoration-none p-3" style="background:linear-gradient(135deg,#ecfdf5,#f0fdf4);border-radius:16px;border:2px solid #a7f3d0;">
                    <div style="font-size:1.75rem;margin-bottom:8px;">📊</div>
                    <strong style="color:#047857;font-size:1.05rem;">Toplu Excel Yükle</strong>
                    <p class="text-muted mb-2 mt-1" style="font-size:.88rem;">Yüzlerce ürün tek seferde — örnek dosyayı indirip doldurun</p>
                    <span class="btn btn-sm btn-success" style="border-radius:10px;">Excel Yükle →</span>
                  </a>
                </div>
                <div class="col-md-4">
                  <a href="{{ route('seller.product.create') }}" class="d-block h-100 text-decoration-none p-3" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:16px;border:2px solid #e2e8f0;">
                    <div style="font-size:1.75rem;margin-bottom:8px;">📝</div>
                    <strong style="color:#334155;font-size:1.05rem;">Detaylı Ürün Ekle</strong>
                    <p class="text-muted mb-2 mt-1" style="font-size:.88rem;">Tüm alanlar, varyant, galeri — tam kontrol</p>
                    <span class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">Form Aç →</span>
                  </a>
                </div>
              </div>
              <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;font-size:.88rem;">
                <span class="text-muted"><i class="fas fa-robot text-primary"></i> Sağ alttaki <strong>AI Asistan</strong> ile fiyat/stok güncelleyin — «Berber koltuğum 12500 TL olsun»</span>
                <a href="{{ route('seller.product-bulk-import-sample') }}" class="text-success"><i class="fas fa-file-excel"></i> Örnek Excel indir</a>
              </div>
            </div>
          </div>
        </div>
      @endif

      <div class="section-body">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Today Order')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $todayOrders->count() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Order')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $totalOrders->count() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Declined Order')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $totalDeclinedOrder }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Complete Order')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $totalOrders->where('order_status',3)->count() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                  <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Today Earning')}}</h4>
                  </div>
                  <div class="card-body">
                      @php
                        $todayEarning = 0;
                        $todayProductSale = 0;
                        foreach ($todayOrders as $key => $todayOrder) {
                            if ((int) $todayOrder->order_status !== 3) continue;
                            $orderProducts = $todayOrder->orderProducts->where('seller_id',$seller->id);
                            foreach ($orderProducts as $key => $orderProduct) {
                                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                                $todayEarning = $todayEarning + $price;
                                $todayProductSale = $todayProductSale + $orderProduct->qty;
                            }
                        }
                      @endphp
                    {{ $setting->currency_icon }}{{ $todayEarning }}
                  </div>
                </div>
              </div>
            </div>

            {{-- Bu Hafta Özeti --}}
            <div class="col-12 mt-3 mb-2"><h5 class="text-muted font-weight-bold">Bu Hafta</h5></div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>Bu Hafta Sipariş</h4>
                  </div>
                  <div class="card-body">
                    {{ $weeklyOrders->count() }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                  <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>Bu Hafta Kazanç</h4>
                  </div>
                  <div class="card-body">
                    {{ $setting->currency_icon }}{{ $weeklyEarning }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>Bu Hafta Satış</h4>
                  </div>
                  <div class="card-body">
                    {{ $weeklyProductSale }}
                  </div>
                </div>
              </div>
            </div>
            {{-- Bu Ay Özeti Başlığı --}}
            <div class="col-12 mt-3 mb-2"><h5 class="text-muted font-weight-bold">Bu Ay</h5></div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.This month Earning')}}</h4>
                  </div>
                  <div class="card-body">
                    @php
                        $thisMonthEarning = 0;
                        $thisMonthProductSale = 0;
                        foreach ($monthlyOrders as $key => $monthlyOrder) {
                            if ((int) $monthlyOrder->order_status !== 3) continue;
                            $orderProducts = $monthlyOrder->orderProducts->where('seller_id',$seller->id);
                            foreach ($orderProducts as $key => $orderProduct) {
                                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                                $thisMonthEarning = $thisMonthEarning + $price;
                                $thisMonthProductSale = $thisMonthProductSale + $orderProduct->qty;
                            }
                        }
                    @endphp
                    {{ $setting->currency_icon }}{{ $thisMonthEarning }}
                  </div>
                </div>
              </div>
            </div>



            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.This Year Earning')}}</h4>
                  </div>
                  <div class="card-body">
                    @php
                        $thisYearEarning = 0;
                        $thisYearProductSale = 0;
                        foreach ($yearlyOrders as $key => $yearlyOrder) {
                            if ((int) $yearlyOrder->order_status !== 3) continue;
                            $orderProducts = $yearlyOrder->orderProducts->where('seller_id',$seller->id);
                            foreach ($orderProducts as $key => $orderProduct) {
                                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                                $thisYearEarning = $thisYearEarning + $price;
                                $thisYearProductSale = $thisYearProductSale + $orderProduct->qty;
                            }
                        }
                    @endphp
                    {{ $setting->currency_icon }}{{ $thisYearEarning }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Earning')}}</h4>
                  </div>
                  <div class="card-body">
                    @php
                        $totalEarning = 0;
                        $totalProductSale = 0;
                        foreach ($totalOrders as $key => $totalOrder) {
                            if ((int) $totalOrder->order_status !== 3) continue;
                            $orderProducts = $totalOrder->orderProducts->where('seller_id',$seller->id);
                            foreach ($orderProducts as $key => $orderProduct) {
                                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                                $totalEarning = $totalEarning + $price;
                                $totalProductSale = $totalProductSale + $orderProduct->qty;
                            }
                        }
                    @endphp
                    {{ $setting->currency_icon }}{{ $totalEarning }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Today Product Sale')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $todayProductSale }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.This Month Product Sale')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $thisMonthProductSale }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.This Year Product Sale')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $thisYearProductSale }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                  <i class="fas fa-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Product Sale')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $totalProductSale }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                  <i class="far fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Product')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $products->count() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Product Review')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $reviews->count() }}
                  </div>
                </div>
              </div>
            </div>


            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-user"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Total Withdraw')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $setting->currency_icon }}{{ $totalWithdraw }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                  <i class="far fa-user"></i>
                </div>
                <div class="card-wrap">
                  <div class="card-header">
                    <h4>{{__('admin.Pending Withraw')}}</h4>
                  </div>
                  <div class="card-body">
                    {{ $setting->currency_icon }}{{ $totalPendingWithdraw }}
                  </div>
                </div>
              </div>
            </div>


          </div>
      </div>

      <div class="section-body">
        <div class="row mt-4">
            <div class="col">
              <div class="card">
                  <div class="card-header">
                      <h3>{{__('admin.Today New Order')}}</h3>
                  </div>
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
                                <th width="10%">{{__('admin.Payment')}}</th>
                                <th width="5%">{{__('admin.Action')}}</th>
                              </tr>
                        </thead>
                        <tbody>
                            @foreach ($todayOrders as $index => $order)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td>{{ $order->user->name }}</td>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $order->created_at->format('d F, Y') }}</td>
                                    <td>{{ $order->product_qty }}</td>
                                    <td>{{ $setting->currency_icon }}{{ $order->amount_real_currency }}</td>
                                    <td>
                                        @if ($order->order_status == 1)
                                        <span class="badge badge-success">{{__('admin.Pregress')}} </span>
                                        @elseif ($order->order_status == 2)
                                        <span class="badge badge-success">{{__('admin.Delivered')}} </span>
                                        @elseif ($order->order_status == 3)
                                        <span class="badge badge-success">{{__('admin.Completed')}} </span>
                                        @elseif ($order->order_status == 4)
                                        <span class="badge badge-danger">{{__('admin.Declined')}} </span>
                                        @else
                                        <span class="badge badge-danger">{{__('admin.Pending')}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->payment_status == 1)
                                        <span class="badge badge-success">{{__('admin.success')}} </span>
                                        @else
                                        <span class="badge badge-danger">{{__('admin.Pending')}}</span>
                                        @endif
                                    </td>

                                    <td>

                                    <a href="{{ route('seller.order-show',$order->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i></a>
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

      {{-- Ürün Bazlı Raporlama — Bu Ayki En Çok Satan Ürünler (#8 revizyon2) --}}
      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Bu Ay — Ürün Bazlı Satış Raporu</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Ürün Adı</th>
                      <th>Satılan Adet</th>
                      <th>Toplam Ciro</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($topProducts as $i => $tp)
                      <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                          @if($tp->product)
                            <a href="{{ route('seller.product.edit', $tp->product_id) }}">{{ $tp->product->short_name ?? $tp->product->name }}</a>
                          @else
                            <span class="text-muted">Silinmiş ürün</span>
                          @endif
                        </td>
                        <td>{{ $tp->total_qty }}</td>
                        <td>{{ $setting->currency_icon }}{{ number_format($tp->total_revenue, 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted">Bu ay henüz satış yok.</td></tr>
                    @endforelse
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

{{--
  Ortak sipariş kalemleri tablosu.
  $invoiceContext: 'admin' | 'seller'
  Admin: miktar +/-, sil; satıcı: salt okunur miktar.
--}}
@php
    $ctx = $invoiceContext ?? 'admin';
@endphp
<div class="table-responsive">
    <table class="table table-striped table-hover table-md">
        <tr>
            <th width="5%">#</th>
            <th width="25%">{{ __('admin.Product') }}</th>
            <th width="20%">{{ __('admin.Variant') }}</th>
            @if ($setting->enable_multivendor == 1)
                <th width="10%">{{ __('admin.Shop Name') }}</th>
            @endif
            <th width="10%" class="text-center">{{ __('admin.Unit Price') }}</th>
            <th width="10%" class="text-center">{{ __('admin.Quantity') }}</th>
            <th width="10%" class="text-right">{{ __('admin.Total') }}</th>
            @if ($ctx === 'admin')
                <th width="10%" class="text-right action-btn">{{ __('admin.Action') }}</th>
            @endif
        </tr>
        @foreach ($order->orderProducts as $index => $orderProduct)
            @php
                $variantPrice = 0;
                $totalVariant = $orderProduct->orderProductVariants->count();
                $lineProduct = $orderProduct->product;
            @endphp
            <tr>
                <td>{{ ++$index }}</td>
                <td>
                    @if ($lineProduct && $lineProduct->slug)
                        <a href="{{ storefront_product_url($lineProduct->slug) }}" target="_blank" rel="noopener">{{ $orderProduct->product_name }}</a>
                    @else
                        {{ $orderProduct->product_name }}
                    @endif
                </td>
                <td>
                    @foreach ($orderProduct->orderProductVariants as $indx => $variant)
                        {{ $variant->variant_name.' : '.$variant->variant_value }}{{ $totalVariant == ++$indx ? '' : ',' }}
                        <br>
                        @php
                            $variantPrice += $variant->variant_price;
                        @endphp
                    @endforeach
                </td>
                @if ($setting->enable_multivendor == 1)
                    <td>
                        @if ($orderProduct->seller)
                            @if ($ctx === 'admin')
                                <a href="{{ route('admin.seller-show', $orderProduct->seller->id) }}">{{ $orderProduct->seller->shop_name }}</a>
                            @else
                                {{ $orderProduct->seller->shop_name }}
                            @endif
                        @endif
                    </td>
                @endif
                <td class="text-center">{{ $setting->currency_icon }}{{ $orderProduct->unit_price }}</td>
                <td class="text-center">
                    @if ($ctx === 'admin')
                        <div class="count">
                            <div class="mainas">
                                <p>
                                    <a href="{{ route('admin.order-quantity-decrement', [$orderProduct->id, $order->id]) }}">
                                        <span>
                                            <svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13 1L1 1" stroke="black" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </span>
                                    </a>
                                </p>
                            </div>
                            <div class="count-text">
                                <input type="number" name="qty_update[289]" value="{{ $orderProduct->qty }}" fdprocessedid="hj0efo">
                            </div>
                            <div class="plus">
                                <p>
                                    <a href="{{ route('admin.order-quantity-increment', [$orderProduct->id, $order->id]) }}">
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7 1V13M13 7L1 7" stroke="black" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </span>
                                    </a>
                                </p>
                            </div>
                        </div>
                    @else
                        {{ $orderProduct->qty }}
                    @endif
                </td>
                @php
                    $total = $orderProduct->unit_price * $orderProduct->qty;
                @endphp
                <td class="text-right">{{ $setting->currency_icon }}{{ $total }}</td>
                @if ($ctx === 'admin')
                    <td class="text-right delete-icon">
                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $orderProduct->id }},{{ $order->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                    </td>
                @endif
            </tr>
        @endforeach
    </table>
</div>

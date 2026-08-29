@php
    /** @var \App\Models\Product $product */
    $publishStatus = app(\App\Support\ProductSellerPublishStatus::class);
    $issues = $publishStatus->issues($product);
@endphp

@if ($publishStatus->isBlockedByAdmin($product))
    <span class="badge badge-danger" title="Admin tarafından yayından kaldırıldı">Admin pasife aldı</span>
@elseif ($issues !== [])
    @foreach ($issues as $issue)
        <span class="badge badge-warning d-inline-block mb-1" title="Yayın için tamamlayın">{{ $issue }}</span>
    @endforeach
@else
    <a href="javascript:;" onclick="changeProductStatus({{ $product->id }})">
        <input type="checkbox" {{ $product->status == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inactive')}}" data-onstyle="success" data-offstyle="danger">
    </a>
@endif

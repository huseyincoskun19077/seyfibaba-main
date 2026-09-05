<div class="alert alert-primary" role="alert">
    <h5>{{__('admin.Withdraw Limit')}} : {{ $setting->currency_icon }}{{ $method->min_amount }} - {{ $setting->currency_icon }}{{ $method->max_amount }}</h5>
    <h5>{{__('admin.Withdraw charge')}} : 0% <small class="text-muted">(platform komisyonu satışta kesilir)</small></h5>
    {!! clean($method->description) !!}
</div>

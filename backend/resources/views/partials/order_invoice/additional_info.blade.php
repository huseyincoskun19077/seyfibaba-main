@if ($order->additional_info)
    <div class="row additional_info">
        <div class="col">
            <hr>
            <h5>{{ __('admin.Additional Information') }}: </h5>
            <p>{!! clean(nl2br($order->additional_info)) !!}</p>
            <hr>
        </div>
    </div>
@endif

{{-- Sadece admin: teslimat görevlisi --}}
@if (($invoiceContext ?? 'admin') === 'admin' && $order->deliveryman)
    <div class="col-md-6">
        <address>
            <strong>Teslimat Görevlisi Bilgileri:</strong><br>
            {{ __('Name') }}: {{ $order->deliveryman->fname }} {{ $order->deliveryman->lname }}<br>
            {{ __('admin.Status') }} :
            @if ($order->order_request == 1)
                <span class="badge badge-success">Kabul Edildi </span>
            @elseif ($order->order_request == 2)
                <span class="badge badge-success">Yoksayıldı </span>
            @elseif ($order->order_status == 3)
                <span class="badge badge-success">{{ __('admin.Completed') }} </span>
            @elseif ($order->order_status == 4)
                <span class="badge badge-danger">{{ __('admin.Declined') }} </span>
            @else
                <span class="badge badge-danger">Yanıt Yok</span>
            @endif
        </address>
    </div>
@endif

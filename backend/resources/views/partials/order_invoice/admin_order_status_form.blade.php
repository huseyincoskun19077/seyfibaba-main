{{-- Sadece admin: sipariş durumu güncelleme --}}
<div class="col-lg-6 order-status">
    <div class="section-title">{{ __('admin.Order Status') }}</div>

    <form action="{{ route('admin.update-order-status', $order->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="">{{ __('admin.Payment') }}</label>
            <select name="payment_status" id="" class="form-control">
                <option {{ $order->payment_status == 0 ? 'selected' : '' }} value="0">{{ __('admin.Pending') }}</option>
                <option {{ $order->payment_status == 1 ? 'selected' : '' }} value="1">{{ __('admin.Success') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="">{{ __('admin.Order') }}</label>
            <select name="order_status" id="" class="form-control">
                <option {{ $order->order_status == 0 ? 'selected' : '' }} value="0">{{ __('admin.Pending') }}</option>
                <option {{ $order->order_status == 1 ? 'selected' : '' }} value="1">{{ __('admin.In Progress') }}</option>
                <option {{ $order->order_status == 2 ? 'selected' : '' }} value="2">{{ __('admin.Delivered') }}</option>
                <option {{ $order->order_status == 3 ? 'selected' : '' }} value="3">{{ __('admin.Completed') }}</option>
                <option {{ $order->order_status == 4 ? 'selected' : '' }} value="4">{{ __('admin.Declined') }}</option>
            </select>
        </div>
        <div class="form-group">
            <label for="">Teslimat Görevlisi Ata</label>
            <select name="delivery_man_id" id="" class="form-control select2">
                <option value="0" {{ $order->order_status == 0 ? 'selected' : '' }}>Seçiniz</option>
                @foreach ($deliverymans as $deliveryman)
                    <option value="{{ $deliveryman->id }}" {{ $order->delivery_man_id == $deliveryman->id ? 'selected' : '' }}>{{ $deliveryman->fname }} {{ $deliveryman->lname }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('admin.Update Status') }}</button>
    </form>
</div>

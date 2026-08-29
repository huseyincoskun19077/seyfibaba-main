<form action="{{ route('admin.shipping.update', $shipping->id) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="row">

        <div class="form-group col-12">
            <label>Şehir / Teslimat Bölgesi <span class="text-danger">*</span></label>
            <select name="city_id" id="" class="form-control select2">
                <option value="0">Tümü</option>
                @foreach ($cities as $city)
                <option {{ $shipping->city_id == $city->id ? 'selected' : '' }} value="{{ $city->id }}">{{ $city->name }}, {{ $city->countryState->name }}, {{ $city->countryState->country->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group col-12">
            <label>Kargo Kuralı Adı <span class="text-danger">*</span></label>
            <input type="text" id="shipping_rule" class="form-control"  name="shipping_rule" autocomplete="off" value="{{ $shipping->shipping_rule }}">
        </div>

        <div class="form-group col-12">
            <label>Tip <span class="text-danger">*</span></label>
            <select name="type" id="edit_type_id" class="form-control">
                <option {{ $shipping->type == 'base_on_price' ? 'selected' : '' }} value="base_on_price">Ürün fiyatına göre</option>
                <option {{ $shipping->type == 'base_on_weight' ? 'selected' : '' }} value="base_on_weight">Ürün ağırlığına göre (g)</option>
                <option {{ $shipping->type == 'base_on_qty' ? 'selected' : '' }} value="base_on_qty">Ürün adedine göre</option>
            </select>
        </div>

        <div class="form-group col-6">
            <label>Koşul Başlangıcı <span class="text-danger">*</span></label>
            <div class="input-group mb-3">
                @if ($shipping->type == 'base_on_price')
                <span class="input-group-text edit_type_class"> {{ $setting->currency_icon }}</span>
                @elseif ($shipping->type == 'base_on_weight')
                <span class="input-group-text edit_type_class">g</span>
                @elseif ($shipping->type == 'base_on_qty')
                <span class="input-group-text edit_type_class">adet</span>
                @endif

                <input type="text" class="form-control" name="condition_from" autocomplete="off" value="{{ $shipping->condition_from }}">
            </div>
        </div>

        <div class="form-group col-6">
            <label>Koşul Bitişi <span class="text-danger">*</span></label>
            <div class="input-group mb-3">
                @if ($shipping->type == 'base_on_price')
                    <span class="input-group-text edit_type_class"> {{ $setting->currency_icon }}</span>
                @elseif ($shipping->type == 'base_on_weight')
                    <span class="input-group-text edit_type_class">g</span>
                @elseif ($shipping->type == 'base_on_qty')
                    <span class="input-group-text edit_type_class">adet</span>
                @endif
                <input type="text" class="form-control" name="condition_to" autocomplete="off" value="{{ $shipping->condition_to }}">
            </div>
        </div>

        <div class="form-group col-12">
            <label>Kargo Ücreti <span class="text-danger">*</span></label>
            <div class="input-group mb-3">
                <span class="input-group-text"> {{ $setting->currency_icon }}</span>
                <input type="text" class="form-control" name="shipping_fee" autocomplete="off" value="{{ $shipping->shipping_fee }}">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <button class="btn btn-primary">Güncelle</button>
        </div>
    </div>
</form>


<script>
(function($) {
    "use strict";
    $(document).ready(function () {
        $('.select2').select2();

        $("#edit_type_id").on("change", function(){
            if($("#edit_type_id").val() == 'base_on_price'){
                $(".edit_type_class").html('{{ $setting->currency_icon }}');
            }else if($("#edit_type_id").val() == 'base_on_weight'){
                $(".edit_type_class").html('g');
            }else if($("#edit_type_id").val() == 'base_on_qty'){
                $(".edit_type_class").html('adet');
            }
        })
    });
})(jQuery);

</script>

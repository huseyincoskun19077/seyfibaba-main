@extends('admin.master_layout')
@section('title')
<title>{{ __('admin.Payment Methods') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>{{ __('admin.Payment Methods') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('admin.Dashboard') }}</a></div>
            </div>
        </div>

        <div class="section-body">
            <div class="row mt-4">
                <div class="col">
                    <div class="card">
                        <div class="card-header"></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-3">
                                    <ul class="nav nav-pills flex-column" id="myTab4" role="tablist">
                                        <li class="nav-item border rounded mb-1">
                                            <a class="nav-link active" id="iyzico-tab" data-toggle="tab" href="#iyzicoTab" role="tab" aria-controls="iyzicoTab" aria-selected="true">Iyzico</a>
                                        </li>
                                        <li class="nav-item border rounded mb-1">
                                            <a class="nav-link" id="bank-account-tab" data-toggle="tab" href="#bankAccountTab" role="tab" aria-controls="bankAccountTab" aria-selected="false">{{ __('admin.Bank Account') }}</a>
                                        </li>
                                        @if ($bank)
                                            <li class="nav-item border rounded mb-1">
                                                <a class="nav-link" id="cash-tab" data-toggle="tab" href="#cashTab" role="tab" aria-controls="cashTab" aria-selected="false">{{ __('admin.Cash On Deliver') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                                <div class="col-12 col-sm-12 col-md-9">
                                    <div class="border rounded">
                                        <div class="tab-content no-padding" id="settingsContent">
                                            <div class="tab-pane fade show active" id="iyzicoTab" role="tabpanel" aria-labelledby="iyzico-tab">
                                                <div class="card m-0">
                                                    <div class="card-body">
                                                        <form action="{{ route('admin.update-iyzico') }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="form-group">
                                                                <label>Iyzico Durumu</label>
                                                                <div>
                                                                    <input id="status_toggle" type="checkbox" {{ optional($iyzico)->status == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{ __('admin.Enable') }}" data-off="{{ __('admin.Disable') }}" data-onstyle="success" data-offstyle="danger" name="status">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Iyzico API Anahtarı</label>
                                                                <input type="text" class="form-control" name="api_key" value="{{ optional($iyzico)->api_key }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Iyzico Gizli Anahtarı</label>
                                                                <input type="text" class="form-control" name="secret_key" value="{{ optional($iyzico)->secret_key }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('admin.Currency Name') }}</label>
                                                                <select name="currency_name" class="form-control select2">
                                                                    <option value="">{{ __('admin.Select Currency') }}</option>
                                                                    @foreach ($currencies as $currency)
                                                                        <option {{ optional($iyzico)->currency_id == $currency->id ? 'selected' : '' }} value="{{ $currency->id }}">{{ $currency->currency_name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Test Modu</label>
                                                                <div>
                                                                    <input id="status_toggle" type="checkbox" {{ optional($iyzico)->is_test_mode == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{ __('admin.Enable') }}" data-off="{{ __('admin.Disable') }}" data-onstyle="success" data-offstyle="danger" name="is_test_mode">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Pazaryeri Modu</label>
                                                                <div>
                                                                    <input id="status_toggle" type="checkbox" {{ optional($iyzico)->marketplace_mode == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{ __('admin.Enable') }}" data-off="{{ __('admin.Disable') }}" data-onstyle="success" data-offstyle="danger" name="marketplace_mode">
                                                                </div>
                                                            </div>
                                                            <hr class="my-3">
                                                            <h6 class="text-primary font-weight-bold mb-3"><i class="fas fa-store mr-1"></i> Alt Üye İşyeri (Sub-Merchant) Ayarları</h6>
                                                            <div class="alert alert-info small">
                                                                <strong>Pazaryeri Modu Kurulum Rehberi:</strong>
                                                                <ol class="mt-2 mb-0 pl-3">
                                                                    <li class="mb-1">
                                                                        <strong>Iyzico Paneli:</strong> <a href="https://merchant.iyzipay.com" target="_blank">merchant.iyzipay.com</a> → Sol menü → <em>Pazaryeri</em> → <em>Alt Üye İşyerleri</em>
                                                                    </li>
                                                                    <li class="mb-1">
                                                                        <strong>Alt Üye Ekleme:</strong> Her satıcı için ayrı alt üye işyeri tanımlayın. Satıcının ticari bilgileri (unvan, IBAN, vergi no) gerekir.
                                                                    </li>
                                                                    <li class="mb-1">
                                                                        <strong>Anahtar Alma:</strong> Alt üye oluşturulduktan sonra Iyzico size bir <code>subMerchantKey</code> verir. Bu anahtarı aşağıdaki alana girin.
                                                                    </li>
                                                                    <li class="mb-1">
                                                                        <strong>API ile Oluşturma (İleri Seviye):</strong> Iyzico API'den <code>/payment/iyzilink/products</code> veya <code>/onboarding</code> endpoint'leri ile alt üye işyeri programatik oluşturulabilir. Detay: <a href="https://dev.iyzipay.com/tr/pazaryeri" target="_blank">dev.iyzipay.com/tr/pazaryeri</a>
                                                                    </li>
                                                                    <li class="mb-0">
                                                                        <strong>Varsayılan Anahtar:</strong> Admin'in kendi mağazası (direkt satış) için. Alt satıcıya ait olmayan siparişlerde bu anahtar kullanılır.
                                                                    </li>
                                                                </ol>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Varsayılan Alt Üye İşyeri Anahtarı</label>
                                                                <input type="text" class="form-control" name="sub_merchant_key" value="{{ optional($iyzico)->sub_merchant_key }}" placeholder="Örn: SUB_MERCHANT_KEY_ADMIN">
                                                                <small class="text-muted">Admin mağazası için Iyzico panelinden alınan sub-merchant key.</small>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Satıcı Alt Üye İşyeri Durumu</label>
                                                                <div class="alert alert-success small">
                                                                    <i class="fas fa-check-circle mr-1"></i>
                                                                    <strong>Otomatik:</strong> Satıcı hesap doğrulaması (KYC) onaylandığında, Iyzico API üzerinden alt üye işyeri anahtarı otomatik oluşturulur. Manuel giriş gerekmez.
                                                                </div>
                                                                <div class="alert alert-light border small">
                                                                    <strong>Süreç:</strong>
                                                                    <ol class="mt-2 mb-0 pl-3">
                                                                        <li>Satıcı profilinde <strong>TC Kimlik</strong>, <strong>IBAN</strong>, <strong>Vergi No</strong> doldurur</li>
                                                                        <li>Satıcı KYC belgelerini yükler</li>
                                                                        <li>Admin KYC'yi <strong>"Onayla"</strong> yapar</li>
                                                                        <li>Sistem Iyzico API'ye sub-merchant kaydı yapar</li>
                                                                        <li>Anahtar satıcıya atanır — ödeme almaya hazır</li>
                                                                    </ol>
                                                                </div>

                                                                @php
                                                                    $registeredVendors = \App\Models\Vendor::whereNotNull('iyzico_sub_merchant_key')->where('iyzico_sub_merchant_key', '!=', '')->get();
                                                                    $pendingVendors = \App\Models\Vendor::where(function($q) {
                                                                        $q->whereNull('iyzico_sub_merchant_key')->orWhere('iyzico_sub_merchant_key', '');
                                                                    })->where('status', 1)->get();
                                                                @endphp

                                                                @if($registeredVendors->count() > 0)
                                                                <div class="mt-3">
                                                                    <strong class="text-success"><i class="fas fa-store mr-1"></i> Kayıtlı Alt Üye İşyerleri ({{ $registeredVendors->count() }}):</strong>
                                                                    <table class="table table-sm table-bordered mt-2">
                                                                        <thead class="thead-light"><tr><th>ID</th><th>Mağaza</th><th>Sub-Merchant Key</th></tr></thead>
                                                                        <tbody>
                                                                            @foreach($registeredVendors as $v)
                                                                            <tr>
                                                                                <td>{{ $v->id }}</td>
                                                                                <td>{{ $v->shop_name }}</td>
                                                                                <td><code class="small">{{ Str::limit($v->iyzico_sub_merchant_key, 30) }}</code></td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                @endif

                                                                @if($pendingVendors->count() > 0)
                                                                <div class="alert alert-warning small mt-2">
                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                    <strong>{{ $pendingVendors->count() }} satıcının</strong> henüz Iyzico kaydı yok:
                                                                    @foreach($pendingVendors as $pv)
                                                                        <span class="badge badge-light">{{ $pv->shop_name }} (ID: {{ $pv->id }})</span>
                                                                    @endforeach
                                                                    — KYC doğrulamasını tamamlayın.
                                                                </div>
                                                                @endif

                                                                <details class="mt-2">
                                                                    <summary class="small text-muted" style="cursor:pointer;">Manuel giriş (gelişmiş)</summary>
                                                                    <textarea name="store_sub_merchant_keys" cols="30" rows="4" class="form-control text-area-5 mt-2">{{ optional($iyzico)->store_sub_merchant_keys != '0' ? optional($iyzico)->store_sub_merchant_keys : '' }}</textarea>
                                                                </details>
                                                            </div>
                                                            <button class="btn btn-primary">{{ __('admin.Update') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="bankAccountTab" role="tabpanel" aria-labelledby="bank-account-tab">
                                                <div class="card m-0">
                                                    <div class="card-body">
                                                        <form action="{{ route('admin.update-bank') }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="form-group">
                                                                <label>{{ __('admin.Bank Payment Status') }}</label>
                                                                <div>
                                                                    <input id="status_toggle" type="checkbox" {{ $bank->status == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{ __('admin.Enable') }}" data-off="{{ __('admin.Disable') }}" data-onstyle="success" data-offstyle="danger" name="status">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Banka Hesap Bilgileri</label>
                                                                <textarea name="account_info" cols="30" rows="10" class="text-area-5 form-control" placeholder="Hesap Sahibi: ...
Banka: ...
IBAN: TR...
Hesap No: ...
Şube: ...">{{ $bank->account_info }}</textarea>
                                                                <small class="text-muted">Müşteriye gösterilecek banka bilgilerini girin. Havale/EFT ödemelerinde bu bilgi sepette görünür.</small>
                                                            </div>
                                                            <button class="btn btn-primary">{{ __('admin.Update') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($bank)
                                                <div class="tab-pane fade" id="cashTab" role="tabpanel" aria-labelledby="cash-tab">
                                                    <div class="card m-0">
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <label>{{ __('admin.Cash on delivery Status') }}</label>
                                                                <div>
                                                                    <a onclick="changeCashOnDeliveryStatus()" href="javascript:;">
                                                                        <input id="status_toggle" type="checkbox" {{ $bank->cash_on_delivery_status == 1 ? 'checked' : '' }} data-toggle="toggle" data-on="{{ __('admin.Enable') }}" data-off="{{ __('admin.Disable') }}" data-onstyle="success" data-offstyle="danger" name="status">
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    function changeCashOnDeliveryStatus() {
        var isDemo = "{{ env('APP_VERSION') }}";
        if (isDemo == 0) {
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }

        $.ajax({
            type: "put",
            data: { _token: '{{ csrf_token() }}' },
            url: "{{ route('admin.update-cash-on-delivery') }}",
            success: function(response) {
                toastr.success(response);
            },
            error: function(err) {
                console.log(err);
            }
        });
    }
</script>
@endsection

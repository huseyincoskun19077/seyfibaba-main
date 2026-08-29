@extends('seller.master_layout')

@section('title')
<title>Satıcı Sözleşmesi</title>
@endsection

@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Satıcı Sözleşmesi</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">Onay Gerekli</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row mt-sm-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info">
                                Panele erişmeden önce satıcı şartları ve koşullarını okuyup onaylamanız zorunludur.
                            </div>

                            <div class="border rounded p-3 mb-4" style="max-height: 420px; overflow-y: auto;">
                                @if($sellerTermsDocument?->content)
                                    {!! $sellerTermsDocument->content !!}
                                @elseif($setting?->seller_condition)
                                    {!! $setting->seller_condition !!}
                                @else
                                    <p class="text-muted mb-0">Satıcı şartları metni yüklenemedi. Lütfen destek ile iletişime geçin.</p>
                                @endif
                            </div>

                            <form action="{{ route('seller.accept-terms.store') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="agree_terms_condition"
                                            name="agree_terms_condition"
                                            value="1"
                                            required
                                        >
                                        <label class="custom-control-label" for="agree_terms_condition">
                                            Satıcı şartları ve koşullarını okudum, kabul ediyorum.
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Sözleşmeyi Onayla ve Devam Et</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@extends('seller.master_layout')
@section('title')
<title>Kargo Ücretleri</title>
@endsection
@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Kargo Ücret Kademeleri</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item active">Kargo Ücretleri</div>
            </div>
        </div>

        <div class="section-body">
            <div class="alert alert-info">
                Müşteri sepetinde <strong>sizin ürünlerinizin alt toplamına</strong> göre kargo ücreti hesaplanır.
                Örnek: 0–499,99 ₺ → 100 ₺ kargo · 500–999,99 ₺ → 100 ₺ · 1000 ₺ ve üzeri → ücretsiz.
                Üst tutarı boş bırakırsanız o kademe sınırsızdır.
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('seller.shipping-tiers.update') }}" id="tiersForm">
                        @csrf
                        @method('PUT')

                        <div class="table-responsive">
                            <table class="table table-bordered" id="tiersTable">
                                <thead>
                                    <tr>
                                        <th>Alt tutar (₺, dahil)</th>
                                        <th>Üst tutar (₺, dahil — boş = sınırsız)</th>
                                        <th>Kargo ücreti (₺)</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tiers as $i => $tier)
                                    <tr>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="tiers[{{ $i }}][min_amount]"
                                                   value="{{ old('tiers.'.$i.'.min_amount', $tier->min_amount) }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="tiers[{{ $i }}][max_amount]"
                                                   value="{{ old('tiers.'.$i.'.max_amount', $tier->max_amount) }}"
                                                   placeholder="Sınırsız">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="tiers[{{ $i }}][shipping_fee]"
                                                   value="{{ old('tiers.'.$i.'.shipping_fee', $tier->shipping_fee) }}" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-tier">Sil</button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary" id="addTierBtn">+ Kademe ekle</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
(function () {
    var tbody = document.querySelector('#tiersTable tbody');
    var addBtn = document.getElementById('addTierBtn');

    function reindex() {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (tr, i) {
            tr.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/tiers\[\d+]/, 'tiers[' + i + ']');
            });
        });
    }

    addBtn.addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="number" step="0.01" min="0" class="form-control" name="tiers[0][min_amount]" value="0" required></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control" name="tiers[0][max_amount]" placeholder="Sınırsız"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control" name="tiers[0][shipping_fee]" value="0" required></td>' +
            '<td><button type="button" class="btn btn-danger btn-sm remove-tier">Sil</button></td>';
        tbody.appendChild(tr);
        reindex();
    });

    tbody.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-tier')) return;
        if (tbody.querySelectorAll('tr').length <= 1) {
            alert('En az bir kademe gerekli.');
            return;
        }
        e.target.closest('tr').remove();
        reindex();
    });
})();
</script>
@endsection

@extends('admin.master_layout')
@section('title')
<title>Ana Sayfa Section Yönetimi</title>
@endsection
@section('admin-content')
      <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Ana Sayfa Section Yönetimi</h1>
            </div>

            <div class="section-body">
                <div class="row mt-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <h4>Section Sıralaması ve Başlıkları</h4>
                                <div class="card-header-action">
                                    <span class="badge badge-info">Sürükle-bırak ile sırala</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.update-homepage-section-title') }}" method="post">
                                    @csrf
                                    <table class="table table-bordered" id="sortable-table">
                                        <thead>
                                            <tr>
                                                <th width="5%" class="text-center">Sıra</th>
                                                <th width="30%">Varsayılan Başlık</th>
                                                <th width="35%">Özel Başlık</th>
                                                <th width="20%">Anahtar</th>
                                                <th width="10%" class="text-center">Taşı</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sortable-body">
                                            @foreach ($sections as $index => $value)
                                                <tr class="sortable-row" data-index="{{ $index }}">
                                                    <td class="text-center align-middle">
                                                        <span class="sort-number badge badge-light">{{ $index + 1 }}</span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>{{ $value->default }}</strong>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="customs[]" value="{{ $value->custom }}" required>
                                                        <input type="hidden" name="defaults[]" value="{{ $value->default }}">
                                                        <input type="hidden" name="keys[]" value="{{ $value->key }}">
                                                    </td>
                                                    <td class="align-middle">
                                                        <code class="small">{{ $value->key }}</code>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="drag-handle" style="cursor:grab; font-size:18px;">
                                                            <i class="fas fa-grip-vertical text-muted"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <div class="alert alert-light border small mt-3">
                                        <i class="fas fa-info-circle text-primary mr-1"></i>
                                        <strong>Sıralama:</strong> Satırları sürükle-bırak ile sıralayabilirsiniz. Sıralama ana sayfadaki section görünüm sırasını belirler.
                                        <strong>Özel Başlık:</strong> Boş bırakırsanız varsayılan başlık kullanılır.
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-2">
                                        <i class="fas fa-save mr-1"></i> Kaydet
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
      </div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function() {
    "use strict";

    var tbody = document.getElementById('sortable-body');
    if (!tbody) return;

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 200,
        ghostClass: 'bg-light',
        onEnd: function() {
            // Sıra numaralarını güncelle
            var rows = tbody.querySelectorAll('.sortable-row');
            rows.forEach(function(row, i) {
                row.querySelector('.sort-number').textContent = i + 1;
            });
        }
    });
})();
</script>
@endsection

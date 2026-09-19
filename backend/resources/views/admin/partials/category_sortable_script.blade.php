{{-- Ortak kategori sürükle-bırak sıralama --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function ($) {
    "use strict";

    var tbody = document.getElementById(@json($sortableBodyId ?? 'sortable-body'));
    if (!tbody) return;

    var reorderUrl = @json($reorderUrl);
    var reorderScope = @json($reorderScope ?? []);
    // Admin layout'ta csrf meta yok; blade token kullan
    var csrf = @json(csrf_token());

    function collectIds() {
        return Array.prototype.map.call(tbody.querySelectorAll('tr[data-id]'), function (row) {
            return parseInt(row.getAttribute('data-id'), 10);
        }).filter(Boolean);
    }

    function refreshNumbers() {
        tbody.querySelectorAll('tr[data-id]').forEach(function (row, i) {
            var sn = row.querySelector('.sort-number');
            if (sn) sn.textContent = String(i + 1);
        });
    }

    function saveOrder() {
        var ids = collectIds();
        if (!ids.length) {
            if (typeof toastr !== 'undefined') toastr.warning('Sıralanacak satır yok.');
            return;
        }

        var payload = {
            _token: csrf,
            ids: ids
        };
        Object.keys(reorderScope || {}).forEach(function (key) {
            payload[key] = reorderScope[key];
        });

        $.ajax({
            type: 'POST',
            url: reorderUrl,
            data: payload,
            success: function (res) {
                if (typeof toastr !== 'undefined') {
                    toastr.success((res && res.message) ? res.message : 'Sıralama güncellendi.');
                }
            },
            error: function (xhr) {
                var msg = 'Sıralama kaydedilemedi.';
                if (xhr.status === 419) {
                    msg = 'Oturum süresi doldu. Sayfayı yenileyip tekrar deneyin.';
                } else if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var first = Object.values(xhr.responseJSON.errors)[0];
                        if (first && first[0]) msg = first[0];
                    }
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            }
        });
    }

    function moveRow(row, direction) {
        if (!row) return;
        if (direction < 0 && row.previousElementSibling) {
            row.parentNode.insertBefore(row, row.previousElementSibling);
        } else if (direction > 0 && row.nextElementSibling) {
            row.parentNode.insertBefore(row.nextElementSibling, row);
        } else {
            return;
        }
        refreshNumbers();
        saveOrder();
    }

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'bg-light',
        onEnd: function () {
            refreshNumbers();
            saveOrder();
        }
    });

    $(tbody).on('click', '.btn-move-up', function () {
        moveRow($(this).closest('tr')[0], -1);
    });
    $(tbody).on('click', '.btn-move-down', function () {
        moveRow($(this).closest('tr')[0], 1);
    });
})(jQuery);
</script>
<style>
  .drag-handle { cursor: grab; color: #64748b; padding: 0 8px; }
  .drag-handle:active { cursor: grabbing; }
  .sort-actions .btn { padding: 2px 8px; }
</style>

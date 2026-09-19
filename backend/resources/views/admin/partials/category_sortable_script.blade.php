{{-- Ortak kategori sürükle-bırak + DataTables arama --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function ($) {
    "use strict";

    $(function () {
        // footer DataTable init'inden sonra çalış
        setTimeout(function () {
            var tableId = @json($tableId ?? 'dataTable');
            var $table = $('#' + tableId);
            var tbody = document.getElementById(@json($sortableBodyId ?? 'sortable-body'));
            if (!$table.length || !tbody) return;

            var reorderUrl = @json($reorderUrl);
            var reorderScope = @json($reorderScope ?? []);
            var csrf = @json(csrf_token());

            if ($.fn.DataTable && $.fn.DataTable.isDataTable($table[0])) {
                $table.DataTable().destroy();
            }

            $table.DataTable({
                ordering: false,
                paging: false,
                info: true,
                autoWidth: false,
                language: {
                    emptyTable: "Tabloda veri bulunamadı",
                    info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                    infoEmpty: "Kayıt bulunamadı",
                    infoFiltered: "(_MAX_ kayıt içinden filtrelendi)",
                    lengthMenu: "_MENU_ kayıt göster",
                    search: "Ara:",
                    zeroRecords: "Eşleşen kayıt bulunamadı"
                }
            });

            function collectIds() {
                return Array.prototype.map.call(tbody.querySelectorAll('tr[data-id]'), function (row) {
                    if (row.style.display === 'none') return 0;
                    return parseInt(row.getAttribute('data-id'), 10);
                }).filter(Boolean);
            }

            function refreshNumbers() {
                var n = 1;
                tbody.querySelectorAll('tr[data-id]').forEach(function (row) {
                    if ($(row).is(':visible')) {
                        var sn = row.querySelector('.sort-number');
                        if (sn) sn.textContent = String(n++);
                    }
                });
            }

            function saveOrder() {
                var ids = collectIds();
                if (!ids.length) return;

                var payload = { _token: csrf, ids: ids };
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
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (typeof toastr !== 'undefined') toastr.error(msg);
                    }
                });
            }

            function moveRow(row, direction) {
                if (!row) return;
                var $row = $(row);
                if (direction < 0) {
                    var $prev = $row.prevAll('tr[data-id]:visible').first();
                    if ($prev.length) $row.insertBefore($prev);
                    else return;
                } else {
                    var $next = $row.nextAll('tr[data-id]:visible').first();
                    if ($next.length) $row.insertAfter($next);
                    else return;
                }
                refreshNumbers();
                saveOrder();
            }

            Sortable.create(tbody, {
                handle: '.drag-handle',
                animation: 180,
                ghostClass: 'bg-light',
                filter: '.dataTables_empty',
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
        }, 80);
    });
})(jQuery);
</script>
<style>
  .drag-handle { cursor: grab; color: #64748b; padding: 0 8px; }
  .drag-handle:active { cursor: grabbing; }
  .sort-actions .btn { padding: 2px 8px; }
</style>

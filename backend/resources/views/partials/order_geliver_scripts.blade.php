{{--
  Geliver AJAX — admin show_order ile birebir aynı (tek fark: $cargoBaseUrl).
  @var string $cargoBaseUrl ör. url('admin/orders') veya url('seller/orders')
  @var \App\Models\Order $order
  (script etiketsiz — üst şablondaki <script> bloğuna dahil edilir)
--}}
(function($) {
    "use strict";

    const orderId = {{ $order->id }};
    const csrfToken = '{{ csrf_token() }}';
    const cargoBase = @json($cargoBaseUrl);

    function showAlert(type, message) {
        const klass = type === 'success' ? 'alert-success' : 'alert-danger';
        const html = `<div class="alert ${klass} mt-3" id="cargoTempAlert">${message}</div>`;
        $('#cargoOffersWrap').before(html);
        setTimeout(() => $('#cargoTempAlert').remove(), 4000);
    }

    function refreshCargoInfo() {
        $.get(`${cargoBase}/${orderId}/cargo`)
            .done(function(response) {
                const cargo = response.data;
                $('#cargoStatusText').text(cargo.status || '-');
                $('#cargoTrackingText').text(cargo.tracking_number || '-');
                $('#cargoCarrierText').text(cargo.carrier_name || '-');

                if (cargo.tracking_url) {
                    $('#cargoTrackingLink').attr('href', cargo.tracking_url).removeClass('d-none');
                } else {
                    $('#cargoTrackingLink').addClass('d-none');
                }

                if (cargo.label_url) {
                    $('#cargoLabelLink').attr('href', cargo.label_url).removeClass('d-none');
                } else {
                    $('#cargoLabelLink').addClass('d-none');
                }

                if (cargo.status !== 'cancelled') {
                    $('#cancelCargoBtn').removeClass('d-none');
                }
            })
            .fail(function() {
                $('#cargoStatusText').text('Kargo kaydı yok');
                $('#cargoTrackingText').text('-');
                $('#cargoCarrierText').text('-');
                $('#cargoTrackingLink, #cargoLabelLink, #cancelCargoBtn').addClass('d-none');
            });
    }

    $('#loadCargoOffers').on('click', function() {
        $.get(`${cargoBase}/${orderId}/cargo/offers`)
            .done(function(response) {
                const offers = response.data.offers || [];
                const defaultOfferId = response.data.default_offer_id;
                let html = '';

                offers.forEach(function(offer, index) {
                    const checked = offer.id === defaultOfferId || (index === 0 && !defaultOfferId) ? 'checked' : '';
                    html += `
                        <tr>
                            <td><input type="radio" name="cargo_offer_id" value="${offer.id}" ${checked}></td>
                            <td>${offer.carrier_name || '-'}</td>
                            <td>${offer.price_text || '-'}</td>
                        </tr>
                    `;
                });

                $('#cargoOffersBody').html(html);
                $('#cargoOffersWrap').removeClass('d-none');
            })
            .fail(function(xhr) {
                showAlert('error', xhr.responseJSON?.message || 'Geliver teklifleri alınamadı.');
            });
    });

    $('#createCargoWithoutOffer').on('click', function() {
        const sellerId = $('#cargoSellerSelect').length ? $('#cargoSellerSelect').val() : null;
        $.ajax({
            url: `${cargoBase}/${orderId}/cargo`,
            method: 'POST',
            data: { _token: csrfToken, seller_id: sellerId },
            success: function(response) {
                showAlert('success', response.message || 'Kargo oluşturuldu.');
                refreshCargoInfo();
            },
            error: function(xhr) {
                showAlert('error', xhr.responseJSON?.message || 'Kargo oluşturulamadı.');
            }
        });
    });

    $('#createCargoWithOffer').on('click', function() {
        const selectedOfferId = $('input[name="cargo_offer_id"]:checked').val();
        const sellerId = $('#cargoSellerSelect').length ? $('#cargoSellerSelect').val() : null;
        $.ajax({
            url: `${cargoBase}/${orderId}/cargo`,
            method: 'POST',
            data: { _token: csrfToken, offer_id: selectedOfferId, seller_id: sellerId },
            success: function(response) {
                showAlert('success', response.message || 'Kargo oluşturuldu.');
                refreshCargoInfo();
            },
            error: function(xhr) {
                showAlert('error', xhr.responseJSON?.message || 'Kargo oluşturulamadı.');
            }
        });
    });

    $('#cancelCargoBtn').on('click', function() {
        $.ajax({
            url: `${cargoBase}/${orderId}/cargo`,
            method: 'POST',
            data: { _token: csrfToken, _method: 'DELETE' },
            success: function(response) {
                showAlert('success', response.message || 'Kargo iptal edildi.');
                refreshCargoInfo();
            },
            error: function(xhr) {
                showAlert('error', xhr.responseJSON?.message || 'Kargo iptal edilemedi.');
            }
        });
    });
})(jQuery);

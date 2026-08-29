@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Commission Settings')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Commission Settings')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Commission Settings')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="row mt-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.update-global-commission-rate') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('admin.Default Commission Rate (%)')}}</label>
                                        <input type="text" class="form-control" name="default_commission_rate" value="{{ $setting->default_commission_rate ?? 0 }}">
                                        <small class="text-muted">{{__('admin.This rate will be applied to all vendors unless they have a customized rate.')}}</small>
                                    </div>
                                    <button class="btn btn-primary">{{__('admin.Update Global Rate')}}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                        <h4>Hakediş & Ödeme Ayarları</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.update-payout-settings') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Otomatik Tamamlama (gün)</label>
                                        <input type="number" min="1" max="60" class="form-control" name="auto_complete_days" value="{{ $setting->auto_complete_days ?? 15 }}">
                                        <small class="text-muted">Teslimden sonra müşteri onaylamazsa sipariş tamamlanır.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Ödeme Bekleme (gün)</label>
                                        <input type="number" min="0" max="30" class="form-control" name="payout_hold_days" value="{{ $setting->payout_hold_days ?? 3 }}">
                                        <small class="text-muted">Tamamlandıktan sonra İyzico onayı için bekleme süresi.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>İade Penceresi (gün)</label>
                                        <input type="number" min="1" max="60" class="form-control" name="return_window_days" value="{{ $setting->return_window_days ?? 14 }}">
                                        <small class="text-muted">Teslimden sonra müşteri iade talep süresi.</small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="custom-switch mt-2">
                                            <input type="checkbox" name="iyzico_payout_dry_run" value="1" class="custom-switch-input" {{ ($setting->iyzico_payout_dry_run ?? false) ? 'checked' : '' }}>
                                            <span class="custom-switch-indicator"></span>
                                            <span class="custom-switch-description">İyzico dry-run (gerçek API çağrısı yapmadan simüle et)</span>
                                        </label>
                                    </div>
                                    <button class="btn btn-primary">Hakediş Ayarlarını Kaydet</button>
                                </div>
                            </div>
                        </form>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                        <h4>{{__('admin.Vendor Specific Commission Rates')}}</h4>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th>{{__('admin.SN')}}</th>
                                    <th>{{__('admin.Shop Name')}}</th>
                                    <th>{{__('admin.Owner')}}</th>
                                    <th>{{__('admin.Override Rate (%)')}}</th>
                                    <th>{{__('admin.Effective Rate (%)')}}</th>
                                    <th>{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach ($vendors as $index => $vendor)
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td>{{ $vendor->shop_name }}</td>
                                        <td>{{ optional($vendor->user)->name ?? '-' }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control vendor-rate" data-id="{{ $vendor->id }}" value="{{ $vendor->commission_rate }}" placeholder="{{__('admin.Default')}} ({{ $setting->default_commission_rate ?? 0 }}%)">
                                        </td>
                                        <td>
                                            <span class="badge badge-info effective-rate" data-id="{{ $vendor->id }}">{{ number_format($vendor->getEffectiveCommissionRate(), 2) }}%</span>
                                        </td>
                                        <td>
                                            <div class="d-flex" style="gap:8px;">
                                                <button type="button" class="btn btn-primary btn-sm save-vendor-rate" data-id="{{ $vendor->id }}">{{__('admin.Save')}}</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm reset-vendor-rate" data-id="{{ $vendor->id }}">{{__('admin.Reset')}}</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </section>
      </div>

<script>
    $(document).ready(function() {
        $('.save-vendor-rate').on('click', function() {
            var id = $(this).data('id');
            var rate = $('.vendor-rate[data-id="' + id + '"]').val();
            
            $.ajax({
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    commission_rate: rate
                },
                url: "{{ url('admin/update-vendor-commission-rate') }}/" + id,
                success: function(response) {
                    var effective = response.vendor && response.vendor.effective_commission_rate !== undefined
                        ? parseFloat(response.vendor.effective_commission_rate).toFixed(2) + '%'
                        : rate + '%';
                    $('.effective-rate[data-id="' + id + '"]').text(effective);
                    toastr.success(response.message);
                },
                error: function(err) {
                    toastr.error('Bir hata oluştu');
                }
            });
        });

        $('.reset-vendor-rate').on('click', function() {
            var id = $(this).data('id');

            $.ajax({
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                url: "{{ url('admin/reset-vendor-commission-rate') }}/" + id,
                success: function(response) {
                    $('.vendor-rate[data-id="' + id + '"]').val('');
                    var effective = response.vendor && response.vendor.effective_commission_rate !== undefined
                        ? parseFloat(response.vendor.effective_commission_rate).toFixed(2) + '%'
                        : '{{ number_format((float) $setting->default_commission_rate, 2) }}%';
                    $('.effective-rate[data-id="' + id + '"]').text(effective);
                    toastr.success(response.message);
                },
                error: function() {
                    toastr.error('Bir hata oluştu');
                }
            });
        });
    });
</script>
@endsection

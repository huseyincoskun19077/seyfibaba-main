@extends('seller.master_layout')
@section('title')
<title>{{__('admin.My withdraw')}}</title>
@endsection
@section('seller-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.My withdraw')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.My withdraw')}}</div>
            </div>
          </div>

          <div class="section-body">
            @if(!empty($earnings['withdraw_request_allowed']))
            <a href="{{ route('seller.my-withdraw.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.New withdraw')}}</a>
            @endif

            <div class="row mt-3">
              <div class="col-md-3">
                <div class="card card-statistic-1">
                  <div class="card-icon bg-primary"><i class="fas fa-coins"></i></div>
                  <div class="card-wrap">
                    <div class="card-header"><h4>Platformdaki toplam</h4></div>
                    <div class="card-body">{{ $setting->currency_icon }}{{ number_format($earnings['total_in_platform'] ?? 0, 2, ',', '.') }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-statistic-1">
                  <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                  <div class="card-wrap">
                    <div class="card-header"><h4>İyzico havuzu (otomatik aktarım)</h4></div>
                    <div class="card-body">{{ $setting->currency_icon }}{{ number_format($earnings['iyzico_pool_balance'] ?? 0, 2, ',', '.') }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-statistic-1">
                  <div class="card-icon bg-success"><i class="fas fa-wallet"></i></div>
                  <div class="card-wrap">
                    <div class="card-header"><h4>{{ __('admin.Withdrawable balance') }} (havale)</h4></div>
                    <div class="card-body">{{ $setting->currency_icon }}{{ number_format($earnings['withdrawable_balance'], 2, ',', '.') }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-statistic-1">
                  <div class="card-icon bg-info"><i class="fas fa-hourglass-half"></i></div>
                  <div class="card-wrap">
                    <div class="card-header"><h4>Havale beklemede</h4></div>
                    <div class="card-body">{{ $setting->currency_icon }}{{ number_format($earnings['bank_pending_hold_balance'] ?? 0, 2, ',', '.') }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-info border mt-2 mb-0">
              <small>{{ $earnings['channel_note'] ?? '' }}</small>
            </div>

            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th >{{__('admin.SN')}}</th>
                                    <th >{{__('admin.Method')}}</th>
                                    <th >{{__('admin.Charge')}}</th>
                                    <th >{{__('admin.Total Amount')}}</th>
                                    <th >{{__('admin.Withdraw Amount')}}</th>
                                    <th >{{__('admin.Status')}}</th>
                                    <th >{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach ($withdraws as $index => $withdraw)
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td>{{ $withdraw->method }}</td>
                                        <td>{{ $setting->currency_icon }}{{ $withdraw->total_amount - $withdraw->withdraw_amount }}</td>
                                        <td>{{ $setting->currency_icon }}{{ $withdraw->total_amount }}</td>
                                        <td>{{ $setting->currency_icon }}{{ $withdraw->withdraw_amount }}</td>
                                        <td>
                                            @if ($withdraw->status==1)
                                            <span class="badge badge-success">{{__('admin.Success')}}</span>
                                            @else
                                            <span class="badge badge-danger">{{__('admin.Pending')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                        <a href="{{ route('seller.my-withdraw.show',$withdraw->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i></a>
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
        </section>
      </div>
@endsection

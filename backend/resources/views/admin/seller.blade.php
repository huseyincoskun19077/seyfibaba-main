@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Seller List')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Seller List')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Seller List')}}</div>
            </div>
          </div>

          <div class="section-body">
              <a href="{{ route('admin.send-email-to-all-seller') }}" class="btn btn-primary">{{__('admin.Send email to all seller')}}</a>
            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th >{{__('admin.SN')}}</th>
                                    <th >{{__('admin.Seller Name')}}</th>
                                    <th >{{__('admin.Email')}}</th>
                                    <th >Kayıt Kaynağı</th>
                                    <th >Çağrı Merkezi</th>
                                    <th >Sözleşme</th>
                                    <th >{{__('admin.Image')}}</th>
                                    <th >{{__('admin.Status')}}</th>
                                    <th >{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach ($sellers as $index => $seller)
                                    @if(! $seller->user)
                                        @continue
                                    @endif
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td>{{ $seller->user->name }}</td>
                                        <td>{{ $seller->user->email }}</td>
                                        <td>
                                            @if(($seller->registration_source ?? '') === 'call_center')
                                                <span class="badge badge-info">Çağrı Merkezi</span>
                                            @elseif(($seller->registration_source ?? '') === 'public_web')
                                                <span class="badge badge-primary">Web Kaydı</span>
                                            @else
                                                <span class="badge badge-secondary">Kendi Kaydı</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(($seller->registration_source ?? '') === 'call_center' && $seller->registeredByAdmin)
                                                {{ $seller->registeredByAdmin->name }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($seller->seller_terms_accepted_at)
                                                <span class="badge badge-success">Onaylı</span>
                                            @else
                                                <span class="badge badge-warning">Bekliyor</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($seller->user->image)
                                            <img src="{{ asset($seller->user->image) }}" class="rounded-circle" alt="" width="80px">
                                            @endif
                                        </td>
                                        <td>
                                            @if($seller->status == 1)
                                            <a href="javascript:;" onclick="manageCustomerStatus({{ $seller->id }})">
                                                <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inctive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>

                                            @else
                                            <a href="javascript:;" onclick="manageCustomerStatus({{ $seller->id }})">
                                                <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>

                                            @endif
                                        </td>
                                        <td>

                                        <a href="{{ route('admin.seller-show',$seller->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i></a>

                                        <a href="{{ route('admin.send-email-to-seller',$seller->id) }}" class="btn btn-success btn-sm"><i class="far fa-envelope" aria-hidden="true"></i></a>

                                        @if($seller->isQuickOnboardingRegistration() && ($seller->user->must_change_password ?? false))
                                            <form method="POST"
                                                  action="{{ route('admin.seller-resend-first-login-sms', $seller->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Giriş SMS\'i aynı şifreyle yeniden gönderilsin mi?');">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm" title="Giriş SMS yeniden gönder">
                                                    <i class="fas fa-sms"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @php
                                            $existProduct = (int) ($seller->products_count ?? 0);
                                        @endphp

                                        @if ($existProduct == 0)
                                            <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $seller->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @else
                                            <a href="javascript:;" data-toggle="modal" data-target="#canNotDeleteModal" class="btn btn-danger btn-sm" disabled><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @endif


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



      <!-- Modal -->
      <div class="modal fade" id="canNotDeleteModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                      <div class="modal-body">
                          {{__('admin.You can not delete this seller. Because there are one or more products and shop account has been created in this seller.')}}
                      </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                </div>
            </div>
        </div>
    </div>

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/seller-delete/") }}'+"/"+id)
    }
    function manageCustomerStatus(id){
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/seller-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){
                console.log(err);

            }
        })
    }
</script>
@endsection

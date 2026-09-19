@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Product Sub Category')}}</title>
@endsection
@section('admin-content')
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Product Sub Category')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.product-category.index') }}">{{__('admin.Product Category')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Product Sub Category')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:10px;">
              <a href="{{ route('admin.product-sub-category.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            </div>

            <div class="card mb-3">
              <div class="card-body">
                <form method="GET" action="{{ route('admin.product-sub-category.index') }}" class="form-inline flex-wrap" style="gap:10px;">
                  <label class="mr-2 mb-0 font-weight-bold">Kategori seç</label>
                  <select name="category_id" class="form-control" style="min-width:260px;" onchange="this.form.submit()">
                    <option value="">— Kategori seçin —</option>
                    @foreach ($categories as $cat)
                      <option value="{{ $cat->id }}" {{ (int) $categoryId === (int) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                  </select>
                  @if ($categoryId)
                    <a href="{{ route('admin.product-sub-category.index') }}" class="btn btn-light">Filtreyi temizle</a>
                  @endif
                </form>
                <p class="text-muted small mb-0 mt-2">Önce kategori seçin; yalnızca o kategorinin alt kategorilerini sıralarsınız.</p>
              </div>
            </div>

            @if (!$categoryId)
              <div class="alert alert-info mb-0">Sıralama için yukarıdan bir kategori seçin.</div>
            @else
            <div class="row">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      @if ($subCategories->isEmpty())
                        <p class="text-muted mb-0">Bu kategoride alt kategori yok.</p>
                      @else
                      <p class="text-muted mb-3">Sıralamak için satırı sürükleyin veya ↑ ↓ kullanın.</p>
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="categoryOrderTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>{{__('admin.SN')}}</th>
                                    <th>{{__('admin.Sub Category')}}</th>
                                    <th>{{__('admin.Slug')}}</th>
                                    <th>{{__('admin.Category')}}</th>
                                    <th>{{__('admin.Status')}}</th>
                                    <th>{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody id="sortable-body">
                                @foreach ($subCategories as $index => $subCategory)
                                    <tr data-id="{{ $subCategory->id }}">
                                        <td class="drag-handle" title="Sürükle"><i class="fas fa-grip-vertical"></i></td>
                                        <td class="sort-number">{{ $index + 1 }}</td>
                                        <td>{{ $subCategory->name }}</td>
                                        <td>{{ $subCategory->slug }}</td>
                                        <td>{{ optional($subCategory->category)->name }}</td>
                                        <td>
                                            @if($subCategory->status == 1)
                                            <a href="javascript:;" onclick="changeProductSubCategoryStatus({{ $subCategory->id }})">
                                                <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inactive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @else
                                            <a href="javascript:;" onclick="changeProductSubCategoryStatus({{ $subCategory->id }})">
                                                <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inactive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @endif
                                        </td>
                                        <td class="sort-actions">
                                        <button type="button" class="btn btn-light btn-sm btn-move-up" title="Yukarı"><i class="fas fa-arrow-up"></i></button>
                                        <button type="button" class="btn btn-light btn-sm btn-move-down" title="Aşağı"><i class="fas fa-arrow-down"></i></button>
                                        <a href="{{ route('admin.product-sub-category.edit',$subCategory->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                        @if ($subCategory->childCategories->count() == 0 && $subCategory->products->count() == 0)
                                            <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $subCategory->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @else
                                            <a href="javascript:;" data-toggle="modal" data-target="#canNotDeleteModal" class="btn btn-danger btn-sm" disabled><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @endif
                                        </td>
                                    </tr>
                                  @endforeach
                            </tbody>
                        </table>
                      </div>
                      @endif
                    </div>
                  </div>
                </div>
            </div>
            @endif
          </div>
        </section>
      </div>

      <div class="modal fade" id="canNotDeleteModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                      <div class="modal-body">
                          {{__('admin.You can not delete this sub category. Because there are one or more child categories or popular sub categories or home page three column categories or products has been created in this sub category.')}}
                      </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                </div>
            </div>
        </div>
    </div>

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/product-sub-category/") }}'+"/"+id)
    }
    function changeProductSubCategoryStatus(id){
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/product-sub-category-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){}
        })
    }
</script>
@if ($categoryId && $subCategories->isNotEmpty())
@include('admin.partials.category_sortable_script', [
  'reorderUrl' => route('admin.product.sub.category.reorder'),
  'reorderScope' => ['category_id' => (int) $categoryId],
])
@endif
@endsection

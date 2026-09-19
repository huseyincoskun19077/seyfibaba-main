@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Product Child Category')}}</title>
@endsection
@section('admin-content')
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Product Child Category')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.product-category.index') }}">{{__('admin.Product Category')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.product-sub-category.index') }}">{{__('admin.Product Sub Category')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Product Child Category')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:10px;">
              <a href="{{ route('admin.product-child-category.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            </div>

            <div class="card mb-3">
              <div class="card-body">
                <form method="GET" action="{{ route('admin.product-child-category.index') }}" id="childFilterForm" class="form-row align-items-end" style="gap:8px 0;">
                  <div class="form-group col-md-4 mb-2">
                    <label class="font-weight-bold">1) Kategori</label>
                    <select name="category_id" id="filter_category_id" class="form-control">
                      <option value="">— Kategori seçin —</option>
                      @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (int) $categoryId === (int) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group col-md-4 mb-2">
                    <label class="font-weight-bold">2) Alt kategori</label>
                    <select name="sub_category_id" id="filter_sub_category_id" class="form-control" {{ $categoryId ? '' : 'disabled' }}>
                      <option value="">— Alt kategori seçin —</option>
                      @foreach ($subCategories as $sub)
                        <option value="{{ $sub->id }}" {{ (int) $subCategoryId === (int) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group col-md-4 mb-2">
                    <button type="submit" class="btn btn-primary">Göster</button>
                    @if ($categoryId || $subCategoryId)
                      <a href="{{ route('admin.product-child-category.index') }}" class="btn btn-light">Temizle</a>
                    @endif
                  </div>
                </form>
                <p class="text-muted small mb-0 mt-1">Önce kategori, sonra alt kategori seçin; yalnızca o gruptaki child’ları sıralarsınız.</p>
              </div>
            </div>

            @if (!$subCategoryId)
              <div class="alert alert-info mb-0">Sıralama için kategori ve alt kategori seçin.</div>
            @else
            <div class="row">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      @if ($childCategories->isEmpty())
                        <p class="text-muted mb-0">Bu alt kategoride child kategori yok.</p>
                      @else
                      <p class="text-muted mb-3">Sıralamak için satırı sürükleyin veya ↑ ↓ kullanın.</p>
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="categoryOrderTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>{{__('admin.SN')}}</th>
                                    <th>{{__('admin.Child Category')}}</th>
                                    <th>{{__('admin.Slug')}}</th>
                                    <th>{{__('admin.Sub Category')}}</th>
                                    <th>{{__('admin.Category')}}</th>
                                    <th>{{__('admin.Status')}}</th>
                                    <th>{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody id="sortable-body">
                                @foreach ($childCategories as $index => $childCategory)
                                    <tr data-id="{{ $childCategory->id }}">
                                        <td class="drag-handle" title="Sürükle"><i class="fas fa-grip-vertical"></i></td>
                                        <td class="sort-number">{{ $index + 1 }}</td>
                                        <td>{{ $childCategory->name }}</td>
                                        <td>{{ $childCategory->slug }}</td>
                                        <td>{{ optional($childCategory->subCategory)->name }}</td>
                                        <td>{{ optional($childCategory->category)->name }}</td>
                                        <td>
                                            @if($childCategory->status == 1)
                                            <a href="javascript:;" onclick="changeProductSubCategoryStatus({{ $childCategory->id }})">
                                                <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @else
                                            <a href="javascript:;" onclick="changeProductSubCategoryStatus({{ $childCategory->id }})">
                                                <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @endif
                                        </td>
                                        <td class="sort-actions">
                                        <button type="button" class="btn btn-light btn-sm btn-move-up" title="Yukarı"><i class="fas fa-arrow-up"></i></button>
                                        <button type="button" class="btn btn-light btn-sm btn-move-down" title="Aşağı"><i class="fas fa-arrow-down"></i></button>
                                        <a href="{{ route('admin.product-child-category.edit',$childCategory->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                        @if ($childCategory->products->count() == 0)
                                            <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $childCategory->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
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
                          {{__('admin.You can not delete this child category. Because there are one or more popular child categories or home page three column categories or products has been created in this child category.')}}
                      </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                </div>
            </div>
        </div>
    </div>

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/product-child-category/") }}'+"/"+id)
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
            url:"{{url('/admin/product-child-category-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){}
        })
    }

    (function ($) {
        $('#filter_category_id').on('change', function () {
            var catId = $(this).val();
            var $sub = $('#filter_sub_category_id');
            $sub.html('<option value="">— Alt kategori seçin —</option>').prop('disabled', true);
            if (!catId) return;
            $.get("{{ url('/admin/subcategory-by-category') }}/" + catId, function (res) {
                if (res && res.subCategories) {
                    $sub.html(res.subCategories).prop('disabled', false);
                }
            });
        });
    })(jQuery);
</script>
@if ($subCategoryId && $childCategories->isNotEmpty())
@include('admin.partials.category_sortable_script', [
  'reorderUrl' => route('admin.product.child.category.reorder'),
  'reorderScope' => ['sub_category_id' => (int) $subCategoryId],
])
@endif
@endsection

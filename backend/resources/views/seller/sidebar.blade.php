@php
    $setting = App\Models\Setting::first();
    $frontendBase = rtrim($setting?->frontend_url ?? config('app.frontend_url', 'https://seyfibaba.com'), '/');
@endphp

<div class="main-sidebar">
    <aside id="sidebar-wrapper">
      <div class="sidebar-brand">
        <a href="{{ route('seller.dashboard') }}"><div class="logo dashboard-logo">
          @if($setting?->logo)
          <img src="{{ asset($setting->logo) }}" alt="logo"/>
          @else
          <span class="text-white font-weight-bold">Seyfibaba</span>
          @endif
        </div></a>
      </div>
      <div class="sidebar-brand sidebar-brand-sm">
        <a href="{{ route('seller.dashboard') }}">{{ $setting?->sidebar_sm_header ?? 'SF' }}</a>
      </div>
      <ul class="sidebar-menu">
          <li class="{{ Route::is('seller.dashboard') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.dashboard') }}"><i class="fas fa-home"></i> <span>{{__('admin.Dashboard')}}</span></a></li>

          <li class="nav-item dropdown {{ Route::is('seller.all-order') || Route::is('seller.order-show') || Route::is('seller.pregress-order') || Route::is('seller.delivered-order') ||  Route::is('seller.completed-order') || Route::is('seller.declined-order') || Route::is('seller.cash-on-delivery')  ? 'active' : '' }}">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-shopping-cart"></i><span>{{__('admin.Orders')}}</span></a>

            <ul class="dropdown-menu">

              <li class="{{ Route::is('seller.all-order') || Route::is('seller.order-show') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.all-order') }}">{{__('admin.All Orders')}}</a></li>
              <li class="{{ Route::is('seller.pregress-order') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.pregress-order') }}">{{__('admin.Progress Orders')}}</a></li>
              <li class="{{ Route::is('seller.delivered-order') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.delivered-order') }}">{{__('admin.Delivered Orders')}}</a></li>
              <li class="{{ Route::is('seller.completed-order') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.completed-order') }}">{{__('admin.Completed Orders')}}</a></li>

              <li class="{{ Route::is('seller.declined-order') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.declined-order') }}">{{__('admin.Declined Orders')}}</a></li>
              <li class="{{ Route::is('seller.cash-on-delivery') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.cash-on-delivery') }}">{{__('admin.Cash On Delivery')}}</a></li>
            </ul>
          </li>


          <li class="nav-item dropdown {{ Route::is('seller.product.*') || Route::is('seller.product-brand.*') || Route::is('seller.product-variant') || Route::is('seller.create-product-variant') || Route::is('seller.edit-product-variant') || Route::is('seller.product-gallery') || Route::is('seller.product-variant-item') || Route::is('seller.create-product-variant-item') || Route::is('seller.edit-product-variant-item') || Route::is('seller.product-review') || Route::is('seller.wholesale') || Route::is('seller.create-wholesale') || Route::is('seller.edit-wholesale') || Route::is('seller.pending-product') || Route::is('admin.product-highlight') || Route::is('seller.show-product-review') || Route::is('seller.stockout-product') || Route::is('seller.product-import-page') || Route::is('seller.product.quick-create') ? 'active' : '' }}">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-th-large"></i><span>{{__('admin.Manage Products')}}</span></a>

            <ul class="dropdown-menu">

            <li class="{{ Route::is('seller.ai-assistant.*') ? 'active' : '' }}"><a class="nav-link" href="#" onclick="document.getElementById('saiFab')?.click();return false;"><i class="fas fa-robot text-primary mr-1"></i> AI Asistan</a></li>

            <li class="{{ Route::is('seller.product.quick-create') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.product.quick-create') }}"><i class="fas fa-bolt text-warning mr-1"></i> Hızlı Ürün Ekle</a></li>

            <li class="{{ Route::is('seller.product-import-page') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.product-import-page') }}"><i class="fas fa-file-excel text-success mr-1"></i> Toplu Excel Yükle</a></li>

            <li><a class="nav-link" href="{{ route('seller.product.create') }}">{{__('admin.Product Create')}}</a></li>

            <li class="{{ Route::is('seller.product.*') || Route::is('seller.product-variant') || Route::is('seller.create-product-variant') || Route::is('seller.edit-product-variant') || Route::is('seller.product-gallery') || Route::is('seller.product-variant-item') || Route::is('seller.create-product-variant-item') || Route::is('seller.edit-product-variant-item') || Route::is('seller.wholesale') || Route::is('seller.create-wholesale') || Route::is('seller.edit-wholesale') || Route::is('admin.product-highlight') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.product.index') }}">{{__('admin.Products')}}</a></li>

            <li class="{{ Route::is('seller.stockout-product') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.stockout-product') }}">{{__('admin.Stock out')}}</a></li>



            <li class="{{ Route::is('seller.product-review') || Route::is('seller.show-product-review') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.product-review') }}">{{__('admin.Product Reviews')}}</a></li>

          <li class="{{ Route::is('seller.brand*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.brand') }}"><i class="fas fa-tag"></i> <span>{{__('admin.Brands')}}</span></a></li>


            </ul>
          </li>

          <li class="{{ Route::is('seller.inventory') || Route::is('seller.stock-history') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.inventory') }}"><i class="fas fa-th-large"></i> <span>{{__('admin.Inventory')}}</span></a></li>

          <li class="{{ Route::is('seller.my-withdraw.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.my-withdraw.index') }}"><i class="far fa-newspaper"></i> <span>{{__('admin.My Withdraw')}}</span></a></li>

          <li class="{{ Route::is('seller.return-requests.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.return-requests.index') }}"><i class="fas fa-undo"></i> <span>İade Talepleri</span></a></li>

          <li class="{{ Route::is('seller.kyc') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.kyc') }}"><i class="fas fa-id-card"></i> <span>Hesap Doğrulama</span></a></li>

          <li class="menu-header mt-3">Yasal Belgeler</li>

          <li class="{{ Route::is('seller.guide') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.guide') }}"><i class="fas fa-book-open"></i> <span>Şartlar ve Tanıtım</span></a></li>

          <li class="nav-item dropdown">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-file-contract"></i><span>Yasal Belgeler</span></a>
            <ul class="dropdown-menu">
              <li><a class="nav-link" href="{{ $frontendBase }}/legal/seller-terms" target="_blank" rel="noopener">Satıcı Şartları</a></li>
              <li><a class="nav-link" href="{{ $frontendBase }}/legal/delivery-return" target="_blank" rel="noopener">Teslimat ve İade</a></li>
              <li><a class="nav-link" href="{{ $frontendBase }}/legal/prohibited-products" target="_blank" rel="noopener">Yasaklı Ürünler</a></li>
              <li><a class="nav-link" href="{{ $frontendBase }}/legal/commission-policy" target="_blank" rel="noopener">Komisyon Politikası</a></li>
              <li><a class="nav-link" href="{{ $frontendBase }}/legal/payout-info" target="_blank" rel="noopener">Hakediş Bilgileri</a></li>
              <li><a class="nav-link" href="{{ route('seller.faq') }}">SSS</a></li>
            </ul>
          </li>

          <li class="menu-header mt-3">Destek</li>

          <li class="{{ Route::is('seller.contact-admin') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.contact-admin') }}"><i class="fas fa-headset"></i> <span>Admin'e Mesaj</span></a></li>

          <li class="{{ Route::is('seller.faq') ? 'active' : '' }}"><a class="nav-link" href="{{ route('seller.faq') }}"><i class="fas fa-question-circle"></i> <span>Sıkça Sorulan Sorular</span></a></li>

          <li class=""><a class="nav-link" href="{{ $frontendBase }}/profile"><i class="fas fa-user"></i> <span>Kullanıcı Paneli</span></a></li>

        </ul>

    </aside>
  </div>

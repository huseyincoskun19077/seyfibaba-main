@include('admin.header')
<body>
<div id="app">
    <div class="main-wrapper">
        <div class="navbar-bg"></div>
        <nav class="navbar navbar-expand-lg main-navbar">
            <div class="form-inline mr-auto">
                <ul class="navbar-nav mr-3">
                    <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
                </ul>
                <span class="navbar-text text-white font-weight-bold ml-2">Seyfibaba Çağrı Merkezi</span>
            </div>
            <ul class="navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                        <i class="far fa-user"></i> {{ Auth::guard('admin')->user()->name }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <form action="{{ route('call-center.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item has-icon text-danger">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        @include('call-center.partials.sidebar')

        @yield('content')
    </div>
</div>
@include('admin.footer')

<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('call-center.dashboard') }}">Çağrı Merkezi</a>
        </div>
        <ul class="sidebar-menu">
            <li class="{{ request()->routeIs('call-center.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('call-center.dashboard') }}">
                    <i class="fas fa-home"></i> <span>Panel</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('call-center.registrations.create') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('call-center.registrations.create') }}">
                    <i class="fas fa-user-plus"></i> <span>Hızlı Kayıt</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('call-center.registrations.index') || request()->routeIs('call-center.registrations.show') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('call-center.registrations.index') }}">
                    <i class="fas fa-list"></i> <span>Kayıtlarım</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('call-center.commissions.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('call-center.commissions.index') }}">
                    <i class="fas fa-coins"></i> <span>Hakedişlerim</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('call-center.sms-campaigns.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('call-center.sms-campaigns.index') }}">
                    <i class="fas fa-sms"></i> <span>SMS Gönder</span>
                </a>
            </li>
        </ul>
    </aside>
</div>

@php
    $navItems = [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['icon' => 'bi-people', 'label' => 'Users', 'route' => 'admin.users.index'],
        ['icon' => 'bi-diagram-3', 'label' => 'Circles', 'route' => 'admin.circles.index'],
        ['icon' => 'bi-activity', 'label' => 'Activities', 'route' => 'admin.activities.index'],
        ['icon' => 'bi-coin', 'label' => 'Coins', 'route' => 'admin.coins.index'],
        ['icon' => 'bi-wallet2', 'label' => 'Wallet & Finance', 'route' => '#'],
        ['icon' => 'bi-chat-dots', 'label' => 'Posts & Moderation', 'route' => '#'],
        ['icon' => 'bi-calendar-event', 'label' => 'Events', 'route' => '#'],
        ['icon' => 'bi-people-fill', 'label' => 'Referrals & Visitors', 'route' => '#'],
        ['icon' => 'bi-life-preserver', 'label' => 'Support & Feedback', 'route' => '#'],
        ['icon' => 'bi-bell', 'label' => 'Notifications & Email', 'route' => '#'],
        ['icon' => 'bi-shield-lock', 'label' => 'Audit & Compliance', 'route' => '#'],
        ['icon' => 'bi-gear', 'label' => 'System Settings', 'route' => '#'],
    ];
@endphp
<aside class="admin-sidebar d-flex flex-column">
    <div class="sidebar-brand d-flex align-items-center">
        <div class="brand-mark me-2"></div>
        <div>
            <div class="fw-bold">Peers Global Unity</div>
            <small class="text-muted">Admin Panel</small>
        </div>
    </div>
    <nav class="flex-grow-1">
        <ul class="nav flex-column">
            @foreach ($navItems as $item)
                <li class="nav-item">
                    @if ($item['route'] === '#')
                        <span class="nav-link disabled">
                            <i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                        </span>
                    @else
                        <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
        </form>
    </div>
</aside>

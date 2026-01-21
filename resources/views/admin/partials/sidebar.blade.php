@php
    $adminUser = Auth::guard('admin')->user();
    $adminUser?->loadMissing('roles:key');
    $isSuper = \App\Support\AdminAccess::isSuper($adminUser);
    $isCircleScoped = \App\Support\AdminAccess::isCircleScoped($adminUser);
    $isGlobalAdmin = \App\Support\AdminAccess::isGlobalAdmin($adminUser);

    $dashboardItem = $isCircleScoped
        ? null
        : ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'route' => 'admin.dashboard'];

    $navItems = $isCircleScoped
        ? [
            ['icon' => 'bi-people', 'label' => 'Peers', 'route' => 'admin.users.index'],
            ['icon' => 'bi-coin', 'label' => 'Coins', 'route' => 'admin.coins.index'],
            ['icon' => 'bi-card-checklist', 'label' => 'Unity Peers Plans', 'route' => 'admin.unity-peers-plans.index'],
            ...($isGlobalAdmin ? [['icon' => 'bi-images', 'label' => 'Event Gallery', 'route' => 'admin.event-gallery.index']] : []),
        ]
        : [
            ['icon' => 'bi-people', 'label' => 'Peers', 'route' => 'admin.users.index'],
            ['icon' => 'bi-diagram-3', 'label' => 'Circles', 'route' => 'admin.circles.index'],
            ['icon' => 'bi-coin', 'label' => 'Coins', 'route' => 'admin.coins.index'],
            ['icon' => 'bi-card-checklist', 'label' => 'Unity Peers Plans', 'route' => 'admin.unity-peers-plans.index'],
            ...($isGlobalAdmin ? [['icon' => 'bi-images', 'label' => 'Event Gallery', 'route' => 'admin.event-gallery.index']] : []),
            ['icon' => 'bi-wallet2', 'label' => 'Wallet & Finance', 'route' => '#'],
            ['icon' => 'bi-chat-dots', 'label' => 'Posts & Moderation', 'route' => '#'],
            ['icon' => 'bi-calendar-event', 'label' => 'Events', 'route' => '#'],
            ['icon' => 'bi-people-fill', 'label' => 'Referrals & Visitors', 'route' => '#'],
            ['icon' => 'bi-life-preserver', 'label' => 'Support & Feedback', 'route' => '#'],
            ['icon' => 'bi-bell', 'label' => 'Notifications & Email', 'route' => '#'],
            ['icon' => 'bi-shield-lock', 'label' => 'Audit & Compliance', 'route' => '#'],
            ['icon' => 'bi-gear', 'label' => 'System Settings', 'route' => '#'],
        ];

    $activityMenu = ($isSuper || $isCircleScoped) ? [
        ['label' => 'Summary', 'route' => 'admin.activities.index'],
        ['label' => 'Testimonials', 'route' => 'admin.activities.testimonials.index'],
        ['label' => 'Requirements', 'route' => 'admin.activities.requirements.index'],
        ['label' => 'Referrals', 'route' => 'admin.activities.referrals.index'],
        ['label' => 'P2P Meetings', 'route' => 'admin.activities.p2p-meetings.index'],
        ['label' => 'Business Deals', 'route' => 'admin.activities.business-deals.index'],
    ] : [];

    $activityActive = request()->routeIs('admin.activities.*');
    $activityExpanded = $activityActive || ! $isGlobalAdmin;
@endphp
<aside class="admin-sidebar d-flex flex-column">
    <div class="text-center mb-2">
        <a href="{{ route('admin.users.index') }}" class="d-inline-block">
            <img
                src="/api/v1/files/019bd9d7-7e13-71fc-8395-0e1dd20a268b"
                alt="Peers Global Unity"
                style="max-height:68px; width:auto;"
                class="d-block mx-auto my-3"
                loading="lazy"
            />
        </a>
    </div>
    <nav class="flex-grow-1">
        <ul class="nav flex-column">
            @if ($dashboardItem)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs($dashboardItem['route']) ? 'active' : '' }}" href="{{ route($dashboardItem['route']) }}">
                        <i class="bi {{ $dashboardItem['icon'] }} me-2"></i>{{ $dashboardItem['label'] }}
                    </a>
                </li>
            @endif
            @if ($activityMenu)
                <li class="nav-item menu-parent {{ $activityExpanded ? 'open' : '' }}">
                    <a class="nav-link d-flex justify-content-between align-items-center {{ $activityExpanded ? 'active' : '' }}" data-bs-toggle="collapse" href="#activitiesSubmenu" role="button" aria-expanded="{{ $activityExpanded ? 'true' : 'false' }}" aria-controls="activitiesSubmenu">
                        <span><i class="bi bi-activity me-2"></i>Activities</span>
                        <i class="bi bi-chevron-right menu-arrow"></i>
                    </a>
                    <div class="collapse {{ $activityExpanded ? 'show' : '' }}" id="activitiesSubmenu">
                        <ul class="nav flex-column ms-3">
                            @foreach ($activityMenu as $item)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endif
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const submenu = document.getElementById('activitiesSubmenu');
            if (!submenu) {
                return;
            }

            const parentItem = submenu.closest('.menu-parent');
            if (!parentItem) {
                return;
            }

            if (submenu.classList.contains('show')) {
                parentItem.classList.add('open');
            }

            submenu.addEventListener('show.bs.collapse', () => {
                parentItem.classList.add('open');
            });

            submenu.addEventListener('hide.bs.collapse', () => {
                parentItem.classList.remove('open');
            });
        });
    </script>
@endpush

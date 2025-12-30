<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col">
            <div class="px-6 py-5 border-b border-slate-200">
                <div class="text-xl font-semibold text-slate-800">Unity Admin</div>
                <div class="text-xs text-slate-500 mt-1">Operations Console</div>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1 px-4">
                    <li><a href="/admin/dashboard" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Dashboard</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Users</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Circles</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Activities &amp; Coins</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Wallet &amp; Finance</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Posts &amp; Moderation</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Events</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Referrals &amp; Visitors</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Support &amp; Feedback</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Notifications &amp; Email</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Ads &amp; Banners</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Reports &amp; Analytics</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">Audit &amp; Compliance</a></li>
                    <li><a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-100 text-sm font-medium text-slate-700">System Settings</a></li>
                </ul>
            </nav>
            <div class="px-4 py-4 border-t border-slate-200 text-xs text-slate-500">
                Future modules plug in here.
            </div>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b border-slate-200">
                <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                    <div class="flex items-center gap-2">
                        <button class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-100 text-slate-700" aria-label="Open navigation">
                            <!-- Mobile menu placeholder -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Admin Console</div>
                            <div class="text-xs text-slate-500">Management Dashboard</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex flex-col items-end">
                            <span class="text-sm font-medium text-slate-800">Admin User</span>
                            <span class="text-xs text-slate-500">{{ session('admin_role') }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 sm:px-6 py-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

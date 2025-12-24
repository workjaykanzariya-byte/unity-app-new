<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex min-h-screen">
    <aside class="w-64 bg-slate-900 text-white flex flex-col">
        <div class="px-6 py-5 text-xl font-semibold border-b border-slate-800">Admin Panel</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2 rounded-md transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between px-3 py-2 rounded-md transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800' }}">
                <span>Users</span>
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-slate-800 space-y-2">
            <div class="text-sm text-slate-300 break-all">{{ auth('admin')->user()?->email }}</div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-3 py-2 text-left text-sm font-semibold bg-red-600 hover:bg-red-500 rounded-md transition">Logout</button>
            </form>
        </div>
    </aside>
    <main class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>
            </div>
        </header>
        <section class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto p-6 space-y-4">
                @if (session('status'))
                    <div class="p-4 bg-green-100 text-green-800 rounded-md border border-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 bg-red-100 text-red-800 rounded-md border border-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-4 bg-red-100 text-red-800 rounded-md border border-red-200">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </main>
</div>
</body>
</html>

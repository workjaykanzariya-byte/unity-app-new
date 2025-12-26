<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unity Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.11/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-slate-900 text-white flex flex-col">
            <div class="px-6 py-5 text-xl font-semibold tracking-tight border-b border-slate-800">Unity Admin</div>
            <nav class="flex-1 px-4 py-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800' : '' }}">Dashboard</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.users.*') ? 'bg-slate-800' : '' }}">Users</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="px-6 py-4 border-b bg-white border-slate-200 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Admin Panel</p>
                    <h1 class="text-xl font-semibold text-slate-900">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="text-sm text-slate-600">
                    {{ optional(auth('admin')->user())->display_name ?? optional(auth('admin')->user())->email ?? 'Admin' }}
                </div>
            </header>

            <section class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>

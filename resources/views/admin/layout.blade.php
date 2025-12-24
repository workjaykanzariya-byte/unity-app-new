<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen">
    <aside class="w-64 bg-gray-900 text-white flex flex-col">
        <div class="px-6 py-4 text-xl font-semibold border-b border-gray-800">Admin Panel</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800' : '' }}">Users</a>
        </nav>
        <div class="px-4 py-4 border-t border-gray-800">
            <div class="text-sm text-gray-300">{{ auth('admin')->user()?->email }}</div>
            <form action="{{ route('admin.logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="w-full px-3 py-2 text-left text-sm font-semibold bg-red-600 hover:bg-red-500 rounded-md">Logout</button>
            </form>
        </div>
    </aside>
    <main class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-6">
                <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>
            </div>
        </header>
        <section class="max-w-7xl mx-auto py-6 px-6">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md border border-red-200">
                    <ul class="list-disc list-inside space-y-1">
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

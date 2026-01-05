<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel</title>
    <style>
        :root {
            --primary: #0ea5e9;
            --dark: #0f172a;
            --muted: #94a3b8;
            --panel: #111827;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0b1222;
            color: #e2e8f0;
        }
        .layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, #0f172a, #0b1222);
            border-right: 1px solid #1f2937;
            padding: 24px 20px;
        }
        .sidebar h2 {
            margin: 0 0 24px;
            font-size: 18px;
            letter-spacing: 0.4px;
        }
        .nav-link {
            display: block;
            padding: 12px 14px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .nav-link:hover {
            background: #0ea5e91a;
            color: #e0f2fe;
        }
        .topbar {
            background: #0f172a;
            border-bottom: 1px solid #1f2937;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pill {
            background: #1e293b;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--muted);
            font-size: 13px;
        }
        .content {
            padding: 24px;
        }
        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #1f2937;
            background: #0ea5e9;
            color: #0b1222;
            cursor: pointer;
            font-weight: 600;
        }
        .btn:hover {
            filter: brightness(1.05);
        }
        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
    </aside>
    <div>
        <div class="topbar">
            <div class="brand">Secure Admin</div>
            <div class="user-info">
                @isset($adminUser)
                    <span class="pill">{{ $adminUser->email }}</span>
                @endisset
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn">Logout</button>
                </form>
            </div>
        </div>
        <main class="content">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>

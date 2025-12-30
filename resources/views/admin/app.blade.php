<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard | Peers Global Unity</title>
    @viteReactRefresh
    @vite('resources/admin/main.tsx')
</head>
<body style="margin:0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background:#0f172a;">
    <div id="admin-app"></div>
</body>
</html>

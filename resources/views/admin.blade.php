<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Admin Panel') }} - Admin</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/admin/main.tsx'])
</head>
<body class="antialiased">
    <div id="admin-root"></div>
</body>
</html>

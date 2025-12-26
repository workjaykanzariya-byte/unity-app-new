<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.11/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center">
    <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">Admin Login</h1>
        <p class="text-sm text-slate-600 mb-6">Enter your email to receive an OTP.</p>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.send_otp') }}" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Send OTP</button>
        </form>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-4xl grid md:grid-cols-2 gap-8 items-center">
        <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-8">
            <div class="mb-6">
                <div class="text-slate-900 text-2xl font-semibold">Admin Login</div>
                <p class="text-sm text-slate-500 mt-1">Sign in with your work email to receive a one-time code.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.login.request') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Work Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        required
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                        placeholder="you@company.com"
                    >
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500">A 4-digit OTP will be valid for 5 minutes.</p>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm"
                    >
                        Send OTP
                    </button>
                </div>
            </form>

            @if (session('otp_requested') || old('otp'))
                <div class="mt-8 border-t border-slate-200 pt-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Enter OTP</div>
                            <p class="text-xs text-slate-500">We sent a 4-digit code to {{ $email ?? 'your email' }}.</p>
                        </div>
                        <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">5 min expiry</span>
                    </div>
                    <form action="{{ route('admin.login.verify') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email', $email) }}">
                        <div>
                            <label for="otp" class="block text-sm font-medium text-slate-700">One-Time Password</label>
                            <input
                                type="text"
                                id="otp"
                                name="otp"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="4"
                                value="{{ old('otp') }}"
                                required
                                class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-center tracking-widest text-lg font-semibold text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                                placeholder="0 0 0 0"
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-slate-500">Limited attempts to keep accounts safe.</div>
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-sm"
                            >
                                Verify &amp; Continue
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
        <div class="hidden md:flex flex-col space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900 mb-3">Security-first Admin Access</div>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li>• OTP-only entry to keep passwords out of band.</li>
                    <li>• 5-minute code expiry and limited attempts.</li>
                    <li>• Admin role check before dashboard access.</li>
                    <li>• Session-based auth isolated from mobile users.</li>
                </ul>
            </div>
            <div class="bg-indigo-600 text-white rounded-2xl p-6 shadow-sm">
                <div class="text-lg font-semibold mb-2">Operations Console</div>
                <p class="text-sm text-indigo-50 leading-relaxed">Manage users, activities, finance, and compliance from a unified admin workspace. Future modules will plug into this panel.</p>
            </div>
        </div>
    </div>
</body>
</html>

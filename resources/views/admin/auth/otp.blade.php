<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify OTP | Peers Global Unity</title>
    <style>
        :root { color-scheme: light; }
        body { margin:0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background:#0f172a; color:#0b1528; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card { background:#fff; padding:32px; border-radius:16px; width:100%; max-width:420px; box-shadow:0 25px 60px rgba(15,23,42,0.25); }
        h1 { margin:0 0 12px; font-size:24px; color:#0f172a; }
        p { margin:0 0 20px; color:#475569; line-height:1.6; }
        label { display:block; font-weight:600; margin-bottom:8px; color:#0f172a; }
        input { width:100%; padding:12px 14px; border-radius:10px; border:1px solid #cbd5e1; font-size:20px; letter-spacing:6px; text-align:center; outline:none; transition:border-color 150ms ease, box-shadow 150ms ease; }
        input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.15); }
        button { width:100%; margin-top:18px; padding:12px 14px; border-radius:10px; border:none; background:linear-gradient(90deg,#2563eb,#7c3aed); color:#fff; font-weight:700; cursor:pointer; font-size:15px; box-shadow:0 12px 30px rgba(37,99,235,0.35); transition:transform 120ms ease, box-shadow 120ms ease; }
        button:disabled { opacity:0.65; cursor:not-allowed; box-shadow:none; }
        button:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 16px 40px rgba(37,99,235,0.45); }
        .status { margin-top:14px; font-size:14px; }
        .error { color:#b91c1c; }
        .success { color:#0f766e; }
        .link { display:block; margin-top:12px; text-align:center; font-size:14px; color:#2563eb; text-decoration:none; }
        .link:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="card">
    <h1>Enter OTP</h1>
    <p>We emailed a 6-digit code to <strong>{{ $email ?? 'your email' }}</strong>. Enter it below to continue.</p>
    <form id="verify-otp-form">
        <label for="otp">One-time passcode</label>
        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="\\d{6}" maxlength="6" required autocomplete="one-time-code" />
        <input type="hidden" id="email" value="{{ $email }}">
        <button type="submit" id="verify-button">Verify &amp; Continue</button>
        <div id="status" class="status"></div>
    </form>
    <a class="link" href="/admin/login">Use a different email</a>
</div>
<script>
    const form = document.getElementById('verify-otp-form');
    const statusEl = document.getElementById('status');
    const button = document.getElementById('verify-button');
    const otpInput = document.getElementById('otp');
    const emailInput = document.getElementById('email');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        statusEl.textContent = '';
        statusEl.className = 'status';

        const otp = otpInput.value.trim();
        const email = emailInput.value.trim();

        if (!otp || !email) {
            statusEl.textContent = 'OTP and email are required.';
            statusEl.classList.add('error');
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch('/admin/api/auth/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ email, otp }),
            });

            if (response.status === 423) {
                const data = await response.json();
                statusEl.textContent = data.message || 'Too many attempts. Please request a new OTP.';
                statusEl.classList.add('error');
                return;
            }

            if (response.ok) {
                statusEl.textContent = 'Success! Redirecting...';
                statusEl.classList.add('success');
                window.location.href = '/admin';
                return;
            }

            const data = await response.json();
            statusEl.textContent = data.message || (data.errors?.otp?.[0] ?? 'Invalid OTP.');
            statusEl.classList.add('error');
        } catch (error) {
            statusEl.textContent = 'Something went wrong. Please try again.';
            statusEl.classList.add('error');
        } finally {
            button.disabled = false;
        }
    });
</script>
</body>
</html>

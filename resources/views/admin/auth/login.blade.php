<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    <style>
        :root {
            --bg: #0b1222;
            --card: #0f172a;
            --primary: #0ea5e9;
            --border: #1f2937;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 20% 20%, #0b3b6f55, transparent 25%), var(--bg);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 32px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }
        p {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.5;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #0b1222;
            color: #e2e8f0;
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
        }
        button {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: #0b1222;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3);
        }
        .muted {
            color: var(--muted);
            font-size: 13px;
            margin-top: 6px;
        }
        .stack {
            display: grid;
            gap: 16px;
        }
        .status {
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            display: none;
        }
        .status.show { display: block; }
        .status.success { background: #0f766e33; color: #34d399; border: 1px solid #115e59; }
        .status.error { background: #7f1d1d33; color: #f87171; border: 1px solid #7f1d1d; }
        .otp-row {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .otp-row input {
            text-align: center;
            letter-spacing: 10px;
            font-weight: 700;
            font-size: 18px;
        }
        .divider {
            height: 1px;
            width: 100%;
            background: var(--border);
            margin: 18px 0;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Admin Login</h1>
    <p>Secure OTP sign-in for administrators. Enter your work email to receive a one-time passcode.</p>

    <div class="stack">
        <div>
            <label for="email">Work Email</label>
            <input id="email" name="email" type="email" placeholder="admin@example.com" autocomplete="email" required>
            <div class="muted">We will send a 4-digit OTP to this address.</div>
        </div>
        <button id="send-otp" type="button">Send OTP</button>
    </div>

    <div class="divider"></div>

    <div class="stack" id="otp-section" style="display: none;">
        <div>
            <label for="otp">One-Time Passcode</label>
            <input id="otp" name="otp" inputmode="numeric" pattern="\\d{4}" maxlength="4" placeholder="••••" autocomplete="one-time-code">
            <div class="muted">Enter the 4-digit code sent to your email.</div>
        </div>
        <button id="verify-otp" type="button">Verify &amp; Login</button>
    </div>

    <div id="status" class="status"></div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
    const sendOtpButton = document.getElementById('send-otp');
    const verifyOtpButton = document.getElementById('verify-otp');
    const statusBox = document.getElementById('status');
    const otpSection = document.getElementById('otp-section');
    const emailInput = document.getElementById('email');
    const otpInput = document.getElementById('otp');

    const showStatus = (message, type = 'error') => {
        statusBox.textContent = message;
        statusBox.className = `status show ${type}`;
    };

    const toggleLoading = (button, isLoading, label) => {
        if (!button) return;
        const defaultLabel = button.dataset.defaultLabel || button.textContent;
        button.dataset.defaultLabel = defaultLabel;
        button.disabled = isLoading;
        button.textContent = isLoading ? (label || defaultLabel) : defaultLabel;
    };

    const handleRequestOtp = async () => {
        statusBox.className = 'status';
        sendOtpButton.dataset.defaultLabel = sendOtpButton.dataset.defaultLabel || sendOtpButton.textContent;
        toggleLoading(sendOtpButton, true, 'Sending...');

        try {
            const response = await fetch('/admin/auth/request-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ email: emailInput.value }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to send OTP');
            }

            otpSection.style.display = 'grid';
            otpInput.focus();
            showStatus('OTP sent to your email.', 'success');
        } catch (error) {
            showStatus(error.message, 'error');
        } finally {
            toggleLoading(sendOtpButton, false);
        }
    };

    const handleVerifyOtp = async () => {
        statusBox.className = 'status';
        verifyOtpButton.dataset.defaultLabel = verifyOtpButton.dataset.defaultLabel || verifyOtpButton.textContent;
        toggleLoading(verifyOtpButton, true, 'Verifying...');

        try {
            const response = await fetch('/admin/auth/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ email: emailInput.value, otp: otpInput.value }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Invalid OTP');
            }

            showStatus('Login successful. Redirecting...', 'success');
            window.location.href = data.redirect || '/admin';
        } catch (error) {
            showStatus(error.message, 'error');
        } finally {
            toggleLoading(verifyOtpButton, false);
        }
    };

    sendOtpButton.addEventListener('click', handleRequestOtp);
    verifyOtpButton.addEventListener('click', handleVerifyOtp);
    otpInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\\D/g, '').slice(0, 4);
    });
</script>
</body>
</html>

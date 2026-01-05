<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    <style>
      :root{
        --bg1:#050b1a;
        --bg2:#0a1a3b;
        --card:#0b1224;
        --border:rgba(255,255,255,.10);
        --text:#e7eefc;
        --muted:rgba(231,238,252,.70);
        --primary:#2f7cff;
        --danger:#ff4d4d;
      }

      body{
        margin:0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        min-height:100vh;
        color:var(--text);
        background:
          radial-gradient(900px 500px at 50% 20%, rgba(47,124,255,.18), transparent 60%),
          radial-gradient(700px 500px at 20% 80%, rgba(120,80,255,.14), transparent 60%),
          linear-gradient(180deg, var(--bg1), var(--bg2));
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px;
      }

      .auth-wrap{
        width:100%;
        max-width: 520px;
      }

      .brand{
        text-align:center;
        margin-bottom:14px;
      }
      .brand h1{
        margin:0;
        font-size:22px;
        font-weight:700;
        letter-spacing:.2px;
      }
      .brand p{
        margin:6px 0 0;
        color:var(--muted);
        font-size:14px;
      }

      .card{
        background: rgba(11,18,36,.92);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.35);
        padding: 22px;
        backdrop-filter: blur(8px);
      }

      .card h2{
        margin:0 0 6px;
        font-size:18px;
        font-weight:700;
      }
      .card .sub{
        margin:0 0 16px;
        color:var(--muted);
        font-size:14px;
        line-height:1.4;
      }

      .field{
        margin: 12px 0;
      }
      label{
        display:block;
        font-size:13px;
        color: var(--muted);
        margin-bottom:8px;
      }
      input{
        width:100%;
        box-sizing:border-box;
        padding: 12px 12px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(255,255,255,.06);
        color: var(--text);
        outline:none;
        font-size:15px;
      }
      input:focus{
        border-color: rgba(47,124,255,.8);
        box-shadow: 0 0 0 4px rgba(47,124,255,.18);
      }

      .row{
        display:flex;
        gap:10px;
        align-items:center;
      }

      .btn{
        width:100%;
        border:0;
        border-radius: 10px;
        padding: 12px 14px;
        font-size:15px;
        font-weight:700;
        cursor:pointer;
        background: var(--primary);
        color:#fff;
        transition: transform .05s ease;
      }
      .btn:active{ transform: scale(.99); }
      .btn:disabled{
        opacity:.65;
        cursor:not-allowed;
      }

      .msg{
        margin-top:12px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size:14px;
        display:none;
      }
      .msg.ok{
        display:block;
        background: rgba(56, 189, 248, .10);
        border: 1px solid rgba(56, 189, 248, .25);
      }
      .msg.err{
        display:block;
        background: rgba(255, 77, 77, .10);
        border: 1px solid rgba(255, 77, 77, .25);
        color: #ffd1d1;
      }

      .otp-grid{
        display:grid;
        grid-template-columns: repeat(4, 1fr);
        gap:10px;
      }
      .otp-grid input{
        text-align:center;
        font-size:20px;
        letter-spacing:2px;
        padding: 12px 0;
      }

      .small{
        margin-top:10px;
        color:var(--muted);
        font-size:13px;
        text-align:center;
      }

      @media (max-width: 480px){
        .auth-wrap{ max-width: 420px; }
        .card{ padding:18px; }
        .brand h1{ font-size:20px; }
      }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="brand">
        <h1>Admin Panel</h1>
        <p>Secure OTP sign-in for administrators</p>
    </div>
    <div class="card">
        <h2>Access Portal</h2>
        <div class="sub">Use your work email to receive a 4-digit one-time passcode.</div>

        <div class="field">
            <label for="email">Work Email</label>
            <input id="email" name="email" type="email" placeholder="admin@example.com" autocomplete="email" required>
        </div>
        <button class="btn" id="sendBtn" type="button">Send OTP</button>

        <div id="otpStep" style="display:none;">
            <div class="field">
                <label>OTP (4 digit)</label>
                <div class="otp-grid">
                    <input class="otpBox" inputmode="numeric" maxlength="1" />
                    <input class="otpBox" inputmode="numeric" maxlength="1" />
                    <input class="otpBox" inputmode="numeric" maxlength="1" />
                    <input class="otpBox" inputmode="numeric" maxlength="1" />
                </div>
            </div>

            <button class="btn" id="verifyBtn">Verify &amp; Login</button>
            <div class="small">
                Didn’t receive? <a href="#" id="resendLink" style="color:#9ec3ff;">Resend OTP</a>
            </div>
        </div>

        <div id="msg" class="msg"></div>
    </div>
</div>

<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const sendBtn = document.getElementById('sendBtn');
  const verifyBtn = document.getElementById('verifyBtn');
  const resendLink = document.getElementById('resendLink');
  const msg = document.getElementById('msg');
  const otpStep = document.getElementById('otpStep');
  const emailInput = document.getElementById('email');
  const otpBoxes = Array.from(document.querySelectorAll('.otpBox'));

  function showMsg(text, type = 'err') {
    msg.textContent = text;
    msg.className = `msg ${type}`;
  }

  function toggleBtn(btn, loading, label) {
    if (!btn) return;
    const defaultLabel = btn.dataset.defaultLabel || btn.textContent;
    btn.dataset.defaultLabel = defaultLabel;
    btn.disabled = loading;
    btn.textContent = loading ? (label || defaultLabel) : defaultLabel;
  }

  function getOtp() {
    return otpBoxes.map(b => (b.value || '')).join('');
  }

  otpBoxes.forEach((box, i) => {
    box.addEventListener('input', (e) => {
      box.value = box.value.replace(/\D/g,'').slice(0,1);
      if (box.value && otpBoxes[i+1]) otpBoxes[i+1].focus();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && otpBoxes[i-1]) otpBoxes[i-1].focus();
    });
  });

  async function requestOtp(isResend = false) {
    showMsg('', ''); msg.style.display = 'none';
    toggleBtn(sendBtn, true, isResend ? 'Resending...' : 'Sending...');

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

      otpStep.style.display = 'block';
      otpBoxes[0].focus();
      showMsg('OTP sent to your email.', 'ok');
      msg.style.display = 'block';
    } catch (error) {
      showMsg(error.message || 'Failed to send OTP', 'err');
      msg.style.display = 'block';
    } finally {
      toggleBtn(sendBtn, false);
    }
  }

  async function verifyOtp() {
    showMsg('', ''); msg.style.display = 'none';
    toggleBtn(verifyBtn, true, 'Verifying...');

    const otp = getOtp();
    if (otp.length !== 4) {
      showMsg('Enter the 4-digit OTP.', 'err');
      msg.style.display = 'block';
      toggleBtn(verifyBtn, false);
      return;
    }

    try {
      const response = await fetch('/admin/auth/verify-otp', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ email: emailInput.value, otp }),
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Invalid OTP');
      }

      showMsg('Login successful. Redirecting...', 'ok');
      msg.style.display = 'block';
      window.location.href = data.redirect || '/admin';
    } catch (error) {
      showMsg(error.message || 'Invalid OTP', 'err');
      msg.style.display = 'block';
    } finally {
      toggleBtn(verifyBtn, false);
    }
  }

  sendBtn.addEventListener('click', () => requestOtp(false));
  verifyBtn.addEventListener('click', verifyOtp);
  resendLink.addEventListener('click', (e) => {
    e.preventDefault();
    requestOtp(true);
  });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login OTP</title>
    <style>
        body {
            background: #0f172a;
            font-family: 'Inter', Arial, sans-serif;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        .card {
            background: linear-gradient(145deg, #111827, #0b1222);
            border: 1px solid #1f2937;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            padding: 32px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: #1e293b;
            color: #93c5fd;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        h1 {
            margin: 0 0 12px 0;
            font-size: 26px;
            color: #f8fafc;
        }
        p {
            margin: 0 0 16px 0;
            line-height: 1.6;
            color: #cbd5e1;
        }
        .otp {
            display: inline-block;
            padding: 18px 28px;
            border-radius: 14px;
            background: #0ea5e9;
            color: #0b1222;
            letter-spacing: 6px;
            font-weight: 700;
            font-size: 22px;
            margin: 12px 0 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="badge">Admin Verification</div>
        <h1>Hello {{ $adminUser->name ?? 'Admin' }},</h1>
        <p>Use the One-Time Passcode below to access the Admin Panel.</p>
        <div class="otp">{{ $otp }}</div>
        <p>This code will expire in 5 minutes. If you did not request this login, you can safely ignore this email.</p>
        <p style="margin-bottom: 0;">Thank you,<br/>Admin Security Team</p>
    </div>
    <div class="footer">
        © {{ now()->year }} Admin Panel. All rights reserved.
    </div>
</div>
</body>
</html>

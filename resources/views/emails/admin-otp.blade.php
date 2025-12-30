<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} Admin OTP</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f7fb; color: #1b1b18; margin: 0; padding: 24px; }
        .container { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        .brand { font-size: 18px; font-weight: 700; letter-spacing: 0.4px; color: #111827; margin-bottom: 12px; }
        .otp { font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #111827; text-align: center; padding: 12px 0; }
        .muted { color: #4b5563; font-size: 14px; margin-top: 12px; line-height: 1.6; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand">{{ $appName }} Admin Panel</div>
        <p>Hello,</p>
        <p>Your one-time passcode for secure admin login is:</p>
        <div class="otp">{{ $otp }}</div>
        <p class="muted">This code expires in {{ $expiryMinutes }} minutes. If you did not request this code, please ignore this email.</p>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb;">
        <h2 style="margin: 0 0 16px; color: #111827;">Your Admin Login OTP</h2>
        <p style="margin: 0 0 12px; color: #374151;">Use the code below to complete your admin login. The code is valid for 10 minutes.</p>
        <div style="font-size: 32px; letter-spacing: 8px; font-weight: bold; color: #1f2937; text-align: center; margin: 24px 0;">{{ $otp }}</div>
        <p style="margin: 0; color: #6b7280;">If you did not request this code, you can safely ignore this email.</p>
    </div>
</body>
</html>

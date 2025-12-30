<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin OTP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; background-color: #f8fafc; padding: 24px;">
<table width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);">
    <tr>
        <td style="padding: 32px;">
            <h1 style="margin-top: 0; font-size: 22px; color: #0f172a;">Your Peers Global Unity Admin OTP</h1>
            <p style="font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
                Use the one-time passcode below to continue signing in to the Admin Panel.
            </p>
            <div style="text-align: center; margin: 24px 0;">
                <div style="display: inline-block; background: #0f172a; color: #ffffff; padding: 14px 28px; font-size: 28px; letter-spacing: 8px; border-radius: 12px; font-weight: bold;">
                    {{ $otp }}
                </div>
            </div>
            <p style="font-size: 14px; line-height: 1.5; color: #475569;">
                This code will expire in {{ $expiresInMinutes }} minutes. If you did not request this code, please ignore this email.
            </p>
            <p style="font-size: 14px; line-height: 1.5; color: #94a3b8; margin-top: 24px;">
                Sent by Peers Global Unity
            </p>
        </td>
    </tr>
</table>
</body>
</html>

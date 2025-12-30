<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Admin Login OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 24px; color: #0f172a;">
    <table width="100%" cellspacing="0" cellpadding="0" style="max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <tr>
            <td>
                <h2 style="margin: 0 0 12px 0; font-size: 20px; color: #0f172a;">Admin Login Verification</h2>
                <p style="margin: 0 0 16px 0; font-size: 14px; color: #475569;">Use the one-time password below to continue signing in to the admin console.</p>

                <div style="text-align: center; padding: 16px 0;">
                    <span style="display: inline-block; padding: 12px 20px; font-size: 24px; font-weight: 700; letter-spacing: 4px; background: #eef2ff; color: #1e293b; border-radius: 10px;">{{ $otp }}</span>
                </div>

                <p style="margin: 12px 0 8px 0; font-size: 14px; color: #475569;">The code expires in <strong>5 minutes</strong>.</p>
                <p style="margin: 0; font-size: 12px; color: #94a3b8;">If you did not request this code, you can ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>

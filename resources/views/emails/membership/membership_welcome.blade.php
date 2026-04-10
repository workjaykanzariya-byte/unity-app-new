<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to your Peers Unity Membership</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
<p>Hello {{ $user->display_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Peer' }},</p>

<p>Welcome to Peers Unity. Your paid membership is now active.</p>
<p>We are glad to have you with us and have attached your membership welcome documents for quick reference.</p>
<p>Thank you for being part of the community.</p>

<p>Warm regards,<br>Peers Global Team</p>
</body>
</html>

<p>Hi {{ $user->display_name ?? $user->first_name ?? 'there' }},</p>

<p>Your login OTP for Peers Global Unity is:</p>

<h2>{{ $otp }}</h2>

<p>This OTP will expire in 15 minutes.</p>

<p>If you did not request this, you can ignore this email.</p>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Notification</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <p>Hello Admin,</p>

    <p>A new activity has been submitted on Peers Global Unity.</p>

    <p><strong>Type:</strong> {{ $activityType ?? 'N/A' }}</p>
    <p><strong>Title:</strong> {{ $activityTitle ?? 'N/A' }}</p>

    @if(!empty($actor))
        <p><strong>Submitted By:</strong>
            {{ $actor->display_name ?? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?: ($actor->email ?? 'Unknown User') }}
        </p>
    @endif

    @if(!empty($activityAttributes))
        <p><strong>Details:</strong></p>
        <pre style="background: #f6f8fa; padding: 12px; border-radius: 6px; white-space: pre-wrap;">{{ json_encode($activityAttributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif

    <p>Regards,<br>Peers Global Unity</p>
</body>
</html>

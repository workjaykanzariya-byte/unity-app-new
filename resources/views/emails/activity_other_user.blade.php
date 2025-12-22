<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activity Created With You</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="color: #111827;">Peers Global Unity</h2>
    <p>Someone created a <strong>{{ $activityType }}</strong> with you.</p>

    <h3 style="margin-top: 20px;">Activity Title</h3>
    <p>{{ $activityTitle }}</p>

    @if($actor)
        <h3 style="margin-top: 20px;">Actor</h3>
        <p>
            {{ $actor->display_name ?? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?? 'N/A' }}
            ({{ $actor->email ?? 'N/A' }})
        </p>
    @endif

    @if($otherUser)
        <h3 style="margin-top: 20px;">Other User</h3>
        <p>
            {{ $otherUser->display_name ?? trim(($otherUser->first_name ?? '') . ' ' . ($otherUser->last_name ?? '')) ?? 'N/A' }}
            ({{ $otherUser->email ?? 'N/A' }})
        </p>
    @endif

    <h3 style="margin-top: 20px;">Activity Details</h3>
    <table style="border-collapse: collapse; width: 100%; font-size: 14px;">
        <thead>
        <tr>
            <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Field</th>
            <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Value</th>
        </tr>
        </thead>
        <tbody>
        @foreach($activityAttributes as $key => $value)
            @php
                $displayValue = is_array($value) || is_object($value)
                    ? json_encode($value, JSON_PRETTY_PRINT)
                    : (string) $value;
            @endphp
            <tr>
                <td style="border-bottom: 1px solid #e5e7eb; padding: 8px; width: 30%;">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td style="border-bottom: 1px solid #e5e7eb; padding: 8px;">
                    @if(is_array($value) || is_object($value))
                        <pre style="margin: 0; font-family: Consolas, monospace; white-space: pre-wrap;">{{ $displayValue }}</pre>
                    @else
                        {{ $displayValue === '' ? '—' : $displayValue }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

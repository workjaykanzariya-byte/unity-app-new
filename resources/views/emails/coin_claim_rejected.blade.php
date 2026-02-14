<!doctype html>
<html>
<head><meta charset="utf-8"><title>Coin Claim Rejected</title></head>
<body style="font-family: Arial, sans-serif; color:#1f2937;">
    <h2 style="margin-bottom: 4px;">{{ $appName }}</h2>
    <p style="margin-top: 0; color:#4b5563;">Your coin claim request has been reviewed and was not approved this time.</p>

    <p><strong>Status:</strong> Rejected</p>
    <p><strong>Activity:</strong> {{ $activityLabel }}</p>
    <p><strong>Request ID:</strong> {{ $claim->id }}</p>
    <p><strong>Submitted At:</strong> {{ optional($claim->created_at)->format('Y-m-d H:i:s') }}</p>
    @if(!empty($claim->admin_note))
        <p><strong>Reason:</strong> {{ $claim->admin_note }}</p>
    @endif

    <h4>Request Summary</h4>
    <ul>
        @foreach($summary as $label => $value)
            <li>
                <strong>{{ $label }}:</strong>
                @if(is_string($value) && str_starts_with($value, rtrim(config('app.url'), '/').'/api/v1/files/'))
                    <a href="{{ $value }}">Download File</a>
                @else
                    {{ $value }}
                @endif
            </li>
        @endforeach
    </ul>
</body>
</html>

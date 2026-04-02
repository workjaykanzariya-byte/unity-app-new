<p>Hello {{ $impact->user?->display_name ?? trim(($impact->user?->first_name ?? '') . ' ' . ($impact->user?->last_name ?? '')) ?: 'Peer' }},</p>
<p>Your Impact has been submitted successfully and is awaiting review.</p>
<ul>
    <li><strong>Action:</strong> {{ $impact->action }}</li>
    <li><strong>Date:</strong> {{ optional($impact->impact_date)->toDateString() }}</li>
    <li><strong>Story:</strong> {{ $impact->story_to_share }}</li>
    <li><strong>Status:</strong> {{ ucfirst($impact->status) }}</li>
</ul>

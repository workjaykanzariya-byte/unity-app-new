<p>Hello {{ $submitter->display_name ?? trim(($submitter->first_name ?? '') . ' ' . ($submitter->last_name ?? '')) ?: 'Peer' }},</p>
<p>Your Impact has been approved successfully.</p>
<ul>
    <li><strong>Action:</strong> {{ $impact->action }}</li>
    <li><strong>Story:</strong> {{ $impact->story_to_share }}</li>
    <li><strong>Status:</strong> {{ ucfirst($impact->status) }}</li>
    <li><strong>Life Impacted:</strong> {{ (int) ($impact->life_impacted ?? 1) }}</li>
    <li><strong>Total Life Impacted:</strong> {{ (int) ($submitter->life_impacted_count ?? 0) }}</li>
</ul>

<div class="peer-card">
    <div class="peer-name fw-semibold text-truncate" style="max-width:220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display:block;">
        {{ ($name ?? '') !== '' ? $name : '—' }}
    </div>
    <div class="small text-muted">{{ ($company ?? '') !== '' ? $company : '—' }}</div>
    <div class="small text-muted">{{ ($city ?? '') !== '' ? $city : 'No City' }}</div>
</div>

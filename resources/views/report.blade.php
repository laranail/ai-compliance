{{-- the point-in-time compliance report; print-ready, self-contained --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AI compliance report</title>
    <style>
        body { font: 14px/1.5 system-ui, sans-serif; margin: 2rem auto; max-width: 60rem; color: #111; }
        h1, h2 { border-bottom: 1px solid #ddd; padding-bottom: .3rem; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: .4rem .6rem; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .status-ok { color: #166534; } .status-review { color: #92400e; }
        .status-fail { color: #991b1b; } .status-na { color: #6b7280; }
        @media print { body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
<h1>AI compliance report</h1>
<p>Generated {{ $generated_at }}</p>

<h2>Summary</h2>
<table>
    <tr>
        <th>Consents granted</th><th>Consents denied</th><th>Providers</th><th>Logged AI events</th>
        <th>Checklist ok</th><th>Review</th><th>Fail</th><th>N/A</th>
    </tr>
    <tr>
        <td>{{ $tiles['consents']['granted'] }}</td>
        <td>{{ $tiles['consents']['denied'] }}</td>
        <td>{{ $tiles['providers'] }}</td>
        <td>{{ $tiles['activity_events'] }}</td>
        <td>{{ $tiles['checklist']['ok'] }}</td>
        <td>{{ $tiles['checklist']['review'] }}</td>
        <td>{{ $tiles['checklist']['fail'] }}</td>
        <td>{{ $tiles['checklist']['na'] }}</td>
    </tr>
</table>

<h3>Consent statistics by type</h3>
<table>
    <tr><th>Consent type</th><th>Granted</th><th>Denied</th></tr>
    @forelse ($tiles['consents']['by_type'] as $slug => $counts)
        <tr><td>{{ $slug }}</td><td>{{ $counts['granted'] }}</td><td>{{ $counts['denied'] }}</td></tr>
    @empty
        <tr><td colspan="3">no consent records yet</td></tr>
    @endforelse
</table>

<h2>Classification</h2>
<table>
    <tr><th>Question</th><th>Answer</th></tr>
    @forelse ($classification as $question => $answer)
        <tr><td>{{ $question }}</td><td>{{ $answer }}</td></tr>
    @empty
        <tr><td colspan="2">the intake has not been completed</td></tr>
    @endforelse
</table>

<h2>Checklist</h2>
@foreach ($checklist as $section => $items)
    <h3>{{ ucfirst((string) $section) }}</h3>
    <table>
        <tr><th style="width: 30%">Item</th><th>Status</th><th>Evidence</th><th>Verified</th></tr>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->label }}<br><small>{{ $item->key }}</small></td>
                <td class="status-{{ $item->status->value }}">{{ $item->status->value }}</td>
                <td>{{ $item->evidence_ref }}</td>
                <td>{{ $item->last_verified_at?->toDateString() }} {{ $item->verified_by }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

<h2>AI provider registry</h2>
<table>
    <tr><th>Name</th><th>Vendor</th><th>Model</th><th>Role</th><th>Trains on our data</th><th>DPA signed</th><th>Due diligence</th></tr>
    @forelse ($providers as $provider)
        <tr>
            <td>{{ $provider->name }}</td>
            <td>{{ $provider->vendor }}</td>
            <td>{{ $provider->model_name }} {{ $provider->model_version }}</td>
            <td>{{ $provider->role }}</td>
            <td>{{ $provider->trains_on_our_data }}</td>
            <td>{{ $provider->dpa_signed_at?->toDateString() ?? '—' }}</td>
            <td>{{ $provider->due_diligence_status }}</td>
        </tr>
    @empty
        <tr><td colspan="7">no providers registered</td></tr>
    @endforelse
</table>

<h2>Policy documents</h2>
<table>
    <tr><th>Document</th><th>Type</th><th>Published version</th><th>Published at</th><th>Active</th></tr>
    @forelse ($documents as $document)
        <tr>
            <td>{{ $document->slug }}</td>
            <td>{{ $document->type->value }}</td>
            <td>{{ $document->publishedVersion?->version ?? 'file only' }}</td>
            <td>{{ $document->publishedVersion?->published_at?->toDateString() }}</td>
            <td>{{ $document->active ? 'yes' : 'no' }}</td>
        </tr>
    @empty
        <tr><td colspan="5">policies not imported</td></tr>
    @endforelse
</table>
</body>
</html>

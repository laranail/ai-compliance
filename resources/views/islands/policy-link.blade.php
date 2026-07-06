{{-- link to another policy document: [[policy-link slug="transparency"]] --}}
@php
    $slug = $props['slug'] ?? null;
    $document = $slug !== null ? app(Simtabi\Laranail\AiCompliance\Policy\PolicyRepository::class)->find($slug) : null;
@endphp

@if ($document !== null)
    <a href="{{ route('laranail.ai-compliance.policy', ['slug' => $document->slug]) }}" class="ai-compliance-policy-link">{{ $document->title }}</a>
@else
    {!! $fallback !!}
@endif

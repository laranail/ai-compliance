{{-- inline consent toggle island: [[consent-toggle type="ai_training"]] --}}
@php
    $type = $props['type'] ?? null;
    $subject = app(Simtabi\Laranail\AiCompliance\Support\CurrentSubject::class)->resolve();
    $granted = $type !== null && $subject !== null
        && app(Simtabi\Laranail\AiCompliance\Consent\ConsentManager::class)->granted($subject, $type);
@endphp

@if ($type !== null)
    <form method="POST" action="{{ route('laranail.ai-compliance.consents') }}" class="ai-compliance-consent-toggle" data-consent-type="{{ $type }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="status" value="{{ $granted ? 'withdrawn' : 'granted' }}">
        <button type="submit">
            {{ $granted ? __('laranail-ai-compliance::ai-compliance.strings.preferences.withdraw') : __('laranail-ai-compliance::ai-compliance.strings.preferences.granted') }}
        </button>
    </form>
@else
    {{ $fallback }}
@endif

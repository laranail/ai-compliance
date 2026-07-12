{{-- the ai disclosure line; rendered before any model output --}}
@if ($disclosure !== null)
    <div {{ $attributes->merge(['class' => 'ai-compliance-disclosure']) }} role="note" data-surface="{{ $surface }}">
        <span class="ai-compliance-disclosure-badge" aria-hidden="true">{{ __('ai-compliance::ai-compliance.strings.disclosure.badge') }}</span>
        <span class="ai-compliance-disclosure-text">{!! $disclosure->html !!}</span>
    </div>
@endif

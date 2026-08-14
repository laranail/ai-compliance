{{-- one compiled policy document with server-rendered islands --}}
@if ($document !== null)
    <article {{ $attributes->merge(['class' => 'ai-compliance-policy']) }} data-slug="{{ $document->slug }}" data-locale="{{ $document->locale }}">
        @if ($showTitle)
            <h1 class="ai-compliance-policy-title">{{ $document->title }}</h1>
        @endif

        @if ($document->isFallback())
            <p class="ai-compliance-policy-fallback" role="note">
                {{ __('laranail-ai-compliance::ai-compliance.strings.policy.fallback_notice', ['locale' => $document->locale]) }}
            </p>
        @endif

        <div class="ai-compliance-policy-body">{!! $html !!}</div>

        @if ($document->version !== null)
            <footer class="ai-compliance-policy-version">
                {{ __('laranail-ai-compliance::ai-compliance.strings.policy.version') }} {{ $document->version }}
            </footer>
        @endif
    </article>
@endif

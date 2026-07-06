{{-- the consent preferences panel; plain forms, no javascript required --}}
<section {{ $attributes->merge(['class' => 'ai-compliance-preferences']) }}>
    <h2 class="ai-compliance-preferences-title">{{ $strings['preferences.title'] ?? '' }}</h2>
    <p class="ai-compliance-preferences-intro">{{ $strings['preferences.intro'] ?? '' }}</p>

    @if (session('ai-compliance.saved'))
        <p class="ai-compliance-preferences-saved" role="status">{{ $strings['preferences.saved'] ?? '' }}</p>
    @endif

    @foreach ($types as $type)
        @php
            $slug = $type['slug'];
            $current = $state[$slug]['status'] ?? $type['default_state'];
            $granted = $current === 'granted';
        @endphp

        <div class="ai-compliance-preference" data-consent-type="{{ $slug }}" @if (in_array($slug, $reconsent, true)) data-reconsent="true" @endif>
            <div class="ai-compliance-preference-heading">
                <strong>{{ $type['label'] ?? $slug }}</strong>
                <span class="ai-compliance-preference-status">
                    {{ $granted ? ($strings['preferences.granted'] ?? '') : ($strings['preferences.denied'] ?? '') }}
                </span>
            </div>

            @if (in_array($slug, $reconsent, true))
                <p class="ai-compliance-preference-reconsent" role="alert">{{ $strings['reconsent.title'] ?? '' }}</p>
            @endif

            @if (!empty($type['short_html']))
                <p class="ai-compliance-preference-short">{!! $type['short_html'] !!}</p>
            @endif

            <form method="POST" action="{{ route('laranail.ai-compliance.consents') }}" class="ai-compliance-preference-form">
                @csrf
                <input type="hidden" name="type" value="{{ $slug }}">
                <input type="hidden" name="status" value="{{ $granted ? 'withdrawn' : 'granted' }}">
                <button type="submit">
                    {{ $granted ? ($strings['preferences.withdraw'] ?? '') : ($strings['preferences.granted'] ?? '') }}
                </button>
            </form>
        </div>
    @endforeach
</section>

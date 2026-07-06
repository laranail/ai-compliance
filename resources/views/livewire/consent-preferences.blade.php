<section class="ai-compliance-preferences" data-livewire="true">
    <h2 class="ai-compliance-preferences-title">{{ $strings['preferences.title'] ?? '' }}</h2>
    <p class="ai-compliance-preferences-intro">{{ $strings['preferences.intro'] ?? '' }}</p>

    @foreach ($types as $type)
        @php
            $slug = $type['slug'];
            $current = $state[$slug]['status'] ?? $type['default_state'];
            $granted = $current === 'granted';
        @endphp

        <div class="ai-compliance-preference" data-consent-type="{{ $slug }}" wire:key="consent-{{ $slug }}">
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

            <button
                type="button"
                wire:click="toggle('{{ $slug }}', '{{ $granted ? 'withdrawn' : 'granted' }}')"
                wire:loading.attr="disabled"
            >
                {{ $granted ? ($strings['preferences.withdraw'] ?? '') : ($strings['preferences.granted'] ?? '') }}
            </button>
        </div>
    @endforeach
</section>

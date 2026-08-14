<div class="ai-compliance-reconsent" @if ($reconsent === []) hidden @endif>
    @if ($reconsent !== [])
        <p class="ai-compliance-reconsent-title" role="alert">
            {{ __('laranail-ai-compliance::ai-compliance.strings.reconsent.title') }}
        </p>

        @foreach ($reconsent as $type)
            <div class="ai-compliance-reconsent-item" data-consent-type="{{ $type }}" wire:key="reconsent-{{ $type }}">
                <span>{{ __('laranail-ai-compliance::ai-compliance.consent_types.' . $type . '.label') }}</span>
                <button type="button" wire:click="regrant('{{ $type }}')">
                    {{ __('laranail-ai-compliance::ai-compliance.strings.reconsent.review') }}
                </button>
            </div>
        @endforeach
    @endif
</div>

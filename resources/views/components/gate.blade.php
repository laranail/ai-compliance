{{-- renders the slot only when the subject may use the feature --}}
@if ($allowed)
    {{ $slot }}
@elseif (isset($fallback))
    {{ $fallback }}
@endif

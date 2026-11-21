@props(['name', 'label', 'placeholder', 'groups', 'selected' => null])
@php
    $selectedId = is_scalar($selected) ? (string) $selected : null;
    $selectedOption = collect($groups)->flatten(1)->first(fn ($option) => (string) $option->id === $selectedId);
@endphp
<fieldset class="field inline-choice">
    <legend>{{ $label }}</legend>
    <details data-inline-choice>
        <summary>
            <span data-choice-label>{{ $selectedOption?->name ?? $placeholder }}</span>
            <span class="choice-chevron" aria-hidden="true">⌄</span>
        </summary>
        <div class="choice-list">
            @foreach ($groups as $group => $options)
                @if ($group !== '')
                    <p class="choice-group">{{ $group }}</p>
                @endif
                @foreach ($options as $option)
                    <label class="choice-option">
                        <input type="radio" name="{{ $name }}" value="{{ $option->id }}"
                            @checked((string) $option->id === $selectedId)
                            @if ($loop->first && $loop->parent->first) required @endif>
                        <span>{{ $option->name }}</span>
                    </label>
                @endforeach
            @endforeach
        </div>
        <button class="btn btn-outline btn-sm choice-done" type="button" data-choice-done>تم</button>
    </details>
</fieldset>

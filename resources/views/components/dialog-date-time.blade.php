@props(['value'])
@if ($value)
    @php
        $date = \Illuminate\Support\Carbon::parse($value);
    @endphp
    <time class="dialog-date-time" datetime="{{ $date->toIso8601String() }}">
        <bdi>{{ $date->format('d/m/Y') }}</bdi>
        <bdi>{{ $date->format('H:i') }}</bdi>
    </time>
@else
    —
@endif

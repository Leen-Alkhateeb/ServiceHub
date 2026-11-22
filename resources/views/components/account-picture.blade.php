@props(['account'])
<span {{ $attributes->class(['account-avatar']) }}>
    {{ mb_substr($account->first_name, 0, 1) }}&nbsp;{{ mb_substr($account->last_name, 0, 1) }}
    @if ($picture = \App\Services\AccountPhoto::url($account->id, $account->profile_image))
        <img src="{{ $picture }}" alt="" width="40" height="40" data-optional-image>
    @endif
</span>

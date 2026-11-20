@props(['account'])
<span {{ $attributes->class(['account-avatar']) }}>
    {{ mb_substr($account->first_name, 0, 1) }}&nbsp;{{ mb_substr($account->last_name, 0, 1) }}
    @if ($picture = $account->profile_image)
        <img src="{{ str_starts_with($picture, 'profile-photos/') ? route('account.picture.show', $account->id) : (str_starts_with($picture, 'https://') ? $picture : asset((str_starts_with($picture, 'storage/') ? '' : 'storage/').ltrim($picture, '/'))) }}" alt="" width="40" height="40">
    @endif
</span>

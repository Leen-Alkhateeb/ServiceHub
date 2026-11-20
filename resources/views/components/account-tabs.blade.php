@props(['tab', 'tabs', 'routeName', 'routeParams' => []])
<nav class="profile-nav account-tabs" aria-label="أقسام الملف الشخصي">
    @foreach ($tabs as $key => $label)
        <a href="{{ route($routeName, ['tab' => $key] + $routeParams) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

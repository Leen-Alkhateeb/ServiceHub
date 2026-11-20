@props(['vendor'])
<a class="card directory-card vendor-directory-card" href="{{ route('vendor.profile', ['vendor' => $vendor->id]) }}">
    <x-directory-photo :picture="$vendor->profile_image" :trade="$vendor->work_type" :account-id="$vendor->id" kind="vendor" />
    <div class="directory-card-row">
        <div class="card-body">
            <span class="directory-trade">{{ $vendor->work_type }}</span>
            <h3>{{ $vendor->first_name }} {{ $vendor->last_name }}</h3>
            <p>{{ $vendor->location }}</p>
        </div>
        <span class="directory-arrow" aria-hidden="true">←</span>
    </div>
</a>

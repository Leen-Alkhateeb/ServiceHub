@props(['action', 'message', 'label' => 'حذف'])
<button class="btn btn-danger btn-sm" type="button" data-archive-action="{{ $action }}" data-archive-message="{{ $message }}">{{ $label }}</button>

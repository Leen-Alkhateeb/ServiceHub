@props(['document' => false])
<span class="file-picker" data-file-picker>
    {{ $slot }}
    <span class="file-picker-display" aria-hidden="true">
        <span class="file-picker-button">{{ $document ? 'اختيار ملف' : 'اختيار صورة' }}</span>
        <bdi class="file-picker-name" data-empty="{{ $document ? 'لم يتم اختيار ملف' : 'لم يتم اختيار صورة' }}">{{ $document ? 'لم يتم اختيار ملف' : 'لم يتم اختيار صورة' }}</bdi>
    </span>
</span>

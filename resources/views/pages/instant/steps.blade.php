<ol class="instant-steps" aria-label="مراحل الخدمة الفورية">
    @foreach(['الطلب', 'العروض', 'التنفيذ', 'الدفع'] as $index => $label)
        <li @class(['instant-step', 'is-reached' => $index <= $currentStep]) @if($index === $currentStep) aria-current="step" @endif>
            {{ $index + 1 }}. {{ $label }}
        </li>
    @endforeach
</ol>

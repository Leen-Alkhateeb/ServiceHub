<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 8 || strlen($value) > 72 || str_contains($value, "\0")
            || ! preg_match('/[A-Z]/', $value) || ! preg_match('/[a-z]/', $value)
            || ! preg_match('/[0-9]/', $value) || ! preg_match('/[\p{P}\p{S}]/u', $value)) {
            $fail('كلمة المرور لازم تكون 8 أحرف على الأقل وبحد أقصى 72 بايت، وفيها حرف إنجليزي كبير وصغير ورقم ورمز.');
        }
    }
}

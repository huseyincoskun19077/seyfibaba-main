<?php

namespace App\Support;

class PasswordRules
{
    public const MIN_LENGTH = 8;

    public const REGEX = '/^(?=.*[a-zA-Z])(?=.*\d).+$/';

    /**
     * @return array<string, array<int, string>>
     */
    public static function registerRules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:' . self::MIN_LENGTH,
                'regex:' . self::REGEX,
                'confirmed',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.regex' => 'Şifre en az bir harf ve bir rakam içermelidir.',
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
            'password_confirmation.required' => trans('user_validation.Confirm password does not match'),
            'password_confirmation.same' => trans('user_validation.Confirm password does not match'),
        ];
    }
}

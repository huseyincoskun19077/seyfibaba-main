<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\GoogleRecaptcha;
use Illuminate\Support\Facades\Schema;
use ReCaptcha\ReCaptcha;

class Captcha implements Rule
{

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        try {
            if (request()->is('api/*') || request()->expectsJson()) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        if (app()->environment('local', 'testing')) {
            return true;
        }

        if (!Schema::hasTable('google_recaptchas')) {
            return true;
        }

        $recaptchaSetting = GoogleRecaptcha::first();
        if (!$recaptchaSetting || !$recaptchaSetting->secret_key || (int) $recaptchaSetting->status !== 1) {
            return true;
        }

        $recaptcha=new ReCaptcha($recaptchaSetting->secret_key);
        $response=$recaptcha->verify($value, $_SERVER['REMOTE_ADDR']);
        return $response->isSuccess();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please complete the recaptcha to submit the form';
    }
}

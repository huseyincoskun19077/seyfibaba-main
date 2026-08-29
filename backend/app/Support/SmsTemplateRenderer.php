<?php

namespace App\Support;

class SmsTemplateRenderer
{
    /**
     * @param  array<string, string>  $variables
     */
    public static function render(string $template, array $variables): string
    {
        $message = $template;

        foreach ($variables as $key => $value) {
            $message = str_replace('{{'.$key.'}}', (string) $value, $message);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $message) ?? $message);
    }
}

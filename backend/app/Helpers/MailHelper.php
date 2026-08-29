<?php

namespace App\Helpers;

use App\Models\EmailConfiguration;
use Illuminate\Support\Facades\Schema;

class MailHelper
{

    public static function setMailConfig(){
        if (app()->environment('local', 'testing')) {
            config(['mail.default' => 'array']);
            config(['mail.from.address' => 'no-reply@shopo.local']);
            return;
        }

        if (!Schema::hasTable('email_configurations')) {
            config(['mail.default' => 'array']);
            config(['mail.from.address' => 'no-reply@shopo.local']);
            return;
        }

        $email_setting=EmailConfiguration::first();
        if (!$email_setting) {
            config(['mail.default' => 'array']);
            config(['mail.from.address' => 'no-reply@shopo.local']);
            return;
        }

        $mailConfig = [
            'transport' => 'smtp',
            'host' => $email_setting->mail_host,
            'port' => $email_setting->mail_port,
            'encryption' => $email_setting->mail_encryption,
            'username' => $email_setting->smtp_username,
            'password' =>$email_setting->smtp_password,
            'timeout' => null
        ];

        config(['mail.mailers.smtp' => $mailConfig]);
        config(['mail.from.address' => $email_setting->email]);
    }
}

<?php

namespace Modules\Email;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Config;
use Throwable;

class EmailServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Email';

    protected function bootModule(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'email');

        try {
            $settings = Models\EmailSetting::getInstance();
            Config::set('mail.default', $settings->mailer ?: config('mail.default'));
            Config::set('mail.mailers.smtp.host', $settings->smtp_host ?: config('mail.mailers.smtp.host'));
            Config::set('mail.mailers.smtp.port', $settings->smtp_port ?: config('mail.mailers.smtp.port'));
            Config::set('mail.mailers.smtp.username', $settings->smtp_username ?: config('mail.mailers.smtp.username'));
            Config::set('mail.mailers.smtp.password', $settings->smtp_password ?: config('mail.mailers.smtp.password'));
            Config::set('mail.from.address', $settings->from_address ?: config('mail.from.address'));
            Config::set('mail.from.name', $settings->from_name ?: config('mail.from.name'));
        } catch (Throwable) {
            // The settings table may not exist during the first migration.
        }
    }
}

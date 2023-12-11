<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Mail\TransportManager;

use App\Models\Form;
use App\Models\Setting;
use App\Service\SettingsService;

use Closure;
use Mail;
use Config;
use App;

class MailDynamicConfig
{
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    private function setSmtpConfigFromSettings($settings)
    {
        if(
            isset($settings["smtp-host"])
            && isset($settings["smtp-username"])
            && isset($settings["smtp-password"])
            && isset($settings["smtp-protocol"])
            && isset($settings["smtp-port"])
            && isset($settings["smtp-sender"])
        ){
            Config::set(
                "mail.mailers.smtp.host",
                $settings["smtp-host"]
            );
            Config::set(
                "mail.mailers.smtp.username",
                $settings["smtp-username"]
            );
            Config::set(
                "mail.mailers.smtp.password",
                $settings["smtp-password"]
            );
            Config::set(
                "mail.mailers.smtp.encryption",
                $settings["smtp-protocol"]
            );
            Config::set(
                "mail.mailers.smtp.port",
                $settings["smtp-port"]
            );
            Config::set(
                "mail.from.address",
                $settings["smtp-sender"]
            );
        }
    }

    private function setDefaultSettings() {

        $host = env('MAIL_HOST');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');
        $protocol = env('MAIL_ENCRYPTION');
        $port = env('MAIL_PORT');
        $sender = env('MAIL_FROM_ADDRESS');
        Config::set("mail.mailers.smtp.host", $host);
        Config::set("mail.mailers.smtp.username", $username);
        Config::set("mail.mailers.smtp.password", $password);
        Config::set("mail.mailers.smtp.encryption", $protocol);
        Config::set("mail.mailers.smtp.port", $port);
        Config::set("mail.from.address", $sender);
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $formId = $request->input("form-id", null);

        if ($formId !== null && is_numeric($formId)) {
            $form = Form::firstWhere("id", $formId);

            if ($form !== null) {
                $settings = SettingsService::getSmtpSettings($form->company_id);
                $this->setSmtpConfigFromSettings($settings);
            }
        } else {
            $this->setDefaultSettings();
        }

        $app = App::getInstance();
        $app->register("Illuminate\Mail\MailServiceProvider");

        return $next($request);
    }
}


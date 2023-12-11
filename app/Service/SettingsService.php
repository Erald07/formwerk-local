<?php 

namespace App\Service;

use App\Models\Setting;

use Config;

class SettingsService
{
    private static function isSettingCorrect($setting, $map)
    {
        return array_key_exists($setting, $map)
            && $map[$setting] !== "";
    }

    private static function mapSettingsBySettingName($settings)
    {
        $mappedSettings = [];
        foreach ($settings as $i => $setting) {
            $mappedSettings[$setting["name"]] = $setting["value"];
        }
        return $mappedSettings;
    }

    private static function getSettings($companyId, $settings)
    {
        return Setting::where("company_id", $companyId)
            ->whereIn("name", $settings)
            ->get();
    }

    public static function getSmtpSettings($companyId)
    {
        $settings = SettingsService::getSettings($companyId, [
            "smtp-host",
            "smtp-username",
            "smtp-password",
            "smtp-protocol",
            "smtp-port",
            "smtp-sender"
        ]);
        return SettingsService::mapSettingsBySettingName($settings);
    }

    public static function getSftpSettings($companyId)
    {
        $settings = SettingsService::getSettings($companyId, [
            "sftp-host",
            "sftp-username",
            "sftp-password",
            "sftp-port",
            "sftp-path"
        ]);
        return SettingsService::mapSettingsBySettingName($settings);
    }

    public static function getSmsSettings($companyId)
    {
        $settings = SettingsService::getSettings($companyId, ["sms-api-key"]);
        return SettingsService::mapSettingsBySettingName($settings);
    }

    public static function getPredefinedValuesSettings($companyId)
    {
        $settings = SettingsService::getSettings(
            $companyId,
            ["predefined-values-secret"]
        );
        return SettingsService::mapSettingsBySettingName($settings);
    }

    public static function areSmtpSettingsConfigured($companyId)
    {
        $smtpSettings = SettingsService::getSmtpSettings($companyId);
        return SettingsService::isSettingCorrect("smtp-host", $smtpSettings)
            && SettingsService::isSettingCorrect("smtp-username", $smtpSettings)
            && SettingsService::isSettingCorrect("smtp-password", $smtpSettings)
            && SettingsService::isSettingCorrect("smtp-protocol", $smtpSettings)
            && SettingsService::isSettingCorrect("smtp-port", $smtpSettings)
            && SettingsService::isSettingCorrect("smtp-sender", $smtpSettings);
    }

    public static function areSftpSettingsConfigured($companyId)
    {
        $smtpSettings = SettingsService::getSftpSettings($companyId);
        return SettingsService::isSettingCorrect("sftp-host", $smtpSettings)
            && SettingsService::isSettingCorrect("sftp-username", $smtpSettings)
            && SettingsService::isSettingCorrect("sftp-password", $smtpSettings)
            && SettingsService::isSettingCorrect("sftp-port", $smtpSettings)
            && SettingsService::isSettingCorrect("sftp-path", $smtpSettings);
    }

    public static function areSmsSettingsConfigured($companyId)
    {
        $smsSettings = SettingsService::getSmsSettings($companyId);
        return SettingsService::isSettingCorrect("sms-api-key", $smsSettings);
    }

    public static function arePredefinedValuesSettingsConfigured($companyId)
    {
        $predefinedValues = SettingsService::getPredefinedValuesSettings(
            $companyId
        );
        return SettingsService::isSettingCorrect(
            "predefined-values-secret",
            $predefinedValues
        );
    }
}


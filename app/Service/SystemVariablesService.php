<?php

namespace App\Service;

use Faker\Factory as Faker;

class SystemVariablesService
{
    private static function getTimeSystemVariables($time)
    {
        return [
            "{{fw_yyyymmdd}}" => date("Ymd", $time),
            "{{fw_yyyymmdd_hhii}}" => date("Ymd_Hi", $time),
            "{{fw_yyyymmdd_hhiiss}}" => date("Ymd_His", $time),
        ];
    }

    public static function getSystemVariables($time)
    {
        $timeSystemVariables = self::getTimeSystemVariables($time);
        return [
            "{{fw_id}}" => Faker::create()->numerify(str_repeat("#", 12)),
            "{{fw_yyyymmdd}}" => $timeSystemVariables["{{fw_yyyymmdd}}"],
            "{{fw_yyyymmdd_hhii}}" => $timeSystemVariables["{{fw_yyyymmdd_hhii}}"],
            "{{fw_yyyymmdd_hhiiss}}" => $timeSystemVariables["{{fw_yyyymmdd_hhiiss}}"],
            "{{fw_random_5}}" => Faker::create()->numerify("#####"),
        ];
    }
}

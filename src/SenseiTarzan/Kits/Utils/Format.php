<?php

namespace SenseiTarzan\Kits\Utils;

use pocketmine\utils\TextFormat;

class Format
{

    public static function nameToId(string $name): string
    {
        return str_replace(array_values(TextFormat::COLORS), "", strtolower(str_replace([" "], ["_"], $name)));
    }

    public static function remainingTime(float $time): float{
        return $time - time();
    }

    public static function formatTime(float $time): array
    {
        return [
            'days' => floor($time / 86400),
            'hours' => floor($time / 3600) % 24,
            'minutes' => floor(($time / 60) % 60),
            'seconds' => $time % 60
        ];
    }

}
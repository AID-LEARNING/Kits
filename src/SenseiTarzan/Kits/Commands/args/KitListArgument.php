<?php

namespace SenseiTarzan\Kits\Commands\args;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Component\KitManager;

class KitListArgument extends StringEnumArgument
{


    public static array $VALUES = [];


    public function parse(string $argument, CommandSender $sender): ?Kit
    {
        return KitManager::getInstance()->getKit($argument);
    }


    public function getValue(string $string)
    {
        return self::$VALUES[strtolower($string)];
    }

    public function getEnumValues(): array
    {
        return array_keys(self::$VALUES);
    }

    public function getTypeName(): string
    {
        return "kit";
    }

    public function getEnumName(): string
    {
        return "KistList";
    }
}
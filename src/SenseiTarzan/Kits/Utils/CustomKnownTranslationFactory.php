<?php

namespace SenseiTarzan\Kits\Utils;

use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Class\Kits\Kit;

class CustomKnownTranslationFactory
{

    public static function error_no_free_place(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_FREE_PLACE);
    }

    public static function error_no_have_permissions(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_HAVE_PERMISSIONS);
    }

    public static function error_not_found_kits_player(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NOT_FOUND_KITS_PLAYER);
    }

    public static function error_not_found_kits_player_admin(Player|string $player): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NOT_FOUND_KITS_PLAYER_ADMIN, ['player' => $player instanceof Player ? $player->getName() : $player]);
    }

    public static function error_no_exist_kit(string $nameKit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_EXIST_KIT, ['nameKit' => $nameKit]);
    }

    public static function add_waiting_period(string $nameKit, float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ADD_WAITING_PERIOD, ['kitName' => $nameKit, 'period' => self::get_format_time($period)]);
    }

    public static function get_waiting_period(string $nameKit, float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::GET_WAITING_PERIOD, ['kitName' => $nameKit, 'period' => self::get_format_time(Format::remainingTime($period))]);
    }

    public static function get_format_time(float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::GET_FORMAT_TIME, Format::formatTime($period));
    }

    public static function success_get_kit(string $kitName): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_GET_KIT, ['kitName' => $kitName]);
    }

    public static function success_open_kit(string $kitName): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_OPEN_KIT, ['kitName' => $kitName]);
    }

    public static function success_add_waiting_period(string $kitName): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_ADD_WAITING_PERIOD, ['kitName' => $kitName]);
    }

    public static function error_not_found_waiting_period(mixed $player, mixed $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NOT_FOUND_WAITING_PERIOD, ['player' => $player instanceof Player ? $player->getName() : $player, 'kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }

    public static function accepted_button(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ACCEPTED_BUTTON);
    }

    public static function cancel_button(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::CANCEL_BUTTON);
    }
    public static function title_kit_index(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_INDEX);
    }
    public static function title_kit_information(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_INFORMATION, ['name' => $name]);
    }
}
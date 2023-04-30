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

    public static function error_no_exist_kit(string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_EXIST_KIT, ['kit' => $kit]);
    }

    public static function add_waiting_period(string $kit, float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ADD_WAITING_PERIOD, ['kit' => $kit, 'period' => self::get_format_time($period)]);
    }

    public static function get_waiting_period(string $kit, float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::GET_WAITING_PERIOD, ['kit' => $kit, 'period' => self::get_format_time(Format::remainingTime($period))]);
    }

    public static function get_format_time(float $period): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::GET_FORMAT_TIME, Format::formatTime($period));
    }

    public static function success_get_kit(string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_GET_KIT, ['kit' => $kit]);
    }

    public static function success_open_kit(string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_OPEN_KIT, ['kit' => $kit]);
    }

    public static function success_add_waiting_period(string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_ADD_WAITING_PERIOD, ['kit' => $kit]);
    }

    public static function error_not_found_waiting_period(mixed $player, mixed $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NOT_FOUND_WAITING_PERIOD, ['player' => $player instanceof Player ? $player->getName() : $player, 'kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }

    public static function accepted_button(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_ACCEPT);
    }

    public static function cancel_button(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_DENIED);
    }
    public static function title_kit_index(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_INDEX);
    }
    public static function title_kit_information(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_INFORMATION, ['kit' => $name]);
    }

    public static function title_kit_editor_form(Kit|string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_EDITOR_FORM, ['kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }

    public static function title_kit_editor_gui(Kit|string $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_EDITOR_GUI, ['kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }

    public static function title_kit_editor_index(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_EDITOR_INDEX);
    }

    public static function buttons_general_information(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_GENERAL_INFORMATION);
    }

    public static function buttons_name(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_NAME);
    }

    public static function buttons_permission(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_PERMISSION);
    }

    public static function buttons_delay(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_DELAY);
    }

    public static function buttons_image(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_IMAGE);
    }

    public static function buttons_description(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_DESCRIPTION);
    }

    public static function buttons_items(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_ITEMS);
    }
    public static function buttons_remove(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_REMOVE);
    }

    public static function success_save_kit(Kit|string $kit)
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_SAVE_KIT, ['kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }

    public static function success_delete_kit(Kit|string $kit)
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_DELETE_KIT, ['kit' => $kit instanceof Kit ? $kit->getName() : $kit]);
    }



    public static function title_kit_create(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_KIT_CREATE);
    }

    public static function content_remove_kit(Kit $kit): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::CONTENT_REMOVE_KIT, ['kit' => $kit->getName()]);
    }

    public static function success_reload_kit(array $kits): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SUCCESS_RELOAD_KIT, ['kits' => implode(', ', array_map(function (Kit $kit) {
            return $kit->getName();
        }, $kits))]);
    }


}
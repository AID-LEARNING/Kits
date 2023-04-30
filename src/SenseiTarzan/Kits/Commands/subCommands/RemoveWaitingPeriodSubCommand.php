<?php

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use SenseiTarzan\Kits\Commands\args\KitListArgument;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

class RemoveWaitingPeriodSubCommand extends BaseSubCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new TargetPlayerArgument(false, "player"));
        $this->registerArgument(1, new KitListArgument("kit", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args["player"];
        $kit = $args["kit"];
        if ($kit === null) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_no_exist_kit("?")));
            return;
        }
        $kitsPlayer = KitsPlayerManager::getInstance()->getPlayer($player);
        if ($kitsPlayer === null) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_not_found_kits_player_admin($player)));
            return;
        }
        if (!$kitsPlayer->hasWaitingPeriod($kit->getId())) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_not_found_waiting_period($player, $kit)));
            return;
        }

        $kitsPlayer->removeWaitingPeriod($kit->getId());
    }

    public function getPermission(): string
    {
        return "kits.command.kit-wp.remove";
    }
}
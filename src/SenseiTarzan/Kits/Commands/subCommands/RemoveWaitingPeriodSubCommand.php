<?php

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use SenseiTarzan\Kits\Commands\type\KitListArgument;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;

class RemoveWaitingPeriodSubCommand extends BaseSubCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new TargetPlayerArgument(false));
        $this->registerArgument(1, new KitListArgument("kit", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args["player"];
        $kit = $args["kit"];

        $kitsPlayer = KitsPlayerManager::getInstance()->getPlayer($player);
        if ($kitsPlayer === null) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_not_found_kits_player_admin($player)));
            return;
        }
        if (!$kitsPlayer->hasWaitingPeriod($kit)) {
            $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::error_not_found_waiting_period($player, $kit)));
            return;
        }

        $kitsPlayer->removeWaitingPeriod($kit);
    }

    public function getPermission(): string
    {
        return "kits.command.kit.removeWaitingPeriod";
    }
}
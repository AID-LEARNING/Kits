<?php

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\libs\SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;

class reloadKitsubCommand extends BaseSubCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        KitManager::getInstance()->reload();
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::reload_kit_successful(KitManager::getInstance()->getKits())));
    }

    public function getPermission(): string
    {
        return "kits.command.kit.reload";
    }
}
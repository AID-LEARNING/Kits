<?php

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
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
        $sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::success_reload_kit(KitManager::getInstance()->getKits())));
    }

    public function getPermission(): string
    {
        return "kits.command.kit.reload";
    }
}
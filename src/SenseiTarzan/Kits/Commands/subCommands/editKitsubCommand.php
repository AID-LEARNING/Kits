<?php

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Component\KitManager;

class editKitsubCommand extends BaseSubCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$this->testPermission($sender)) {
            return;
        }
        if (!$sender instanceof Player) {
            return;
        }
        KitManager::getInstance()->UIEditIndex($sender);
    }

    public function getPermission(): string
    {
        return "kits.command.kit.edit";
    }
}
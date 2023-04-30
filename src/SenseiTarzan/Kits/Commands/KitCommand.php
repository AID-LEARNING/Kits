<?php

namespace SenseiTarzan\Kits\Commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Commands\subCommands\createKitSubCommand;
use SenseiTarzan\Kits\Commands\subCommands\editKitsubCommand;
use SenseiTarzan\Kits\Commands\subCommands\reloadKitsubCommand;
use SenseiTarzan\Kits\Component\KitManager;

class KitCommand extends BaseCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerSubCommand(new createKitSubCommand($this->plugin, "create", "Create a kit", ["c", "cr", "make"]));
        $this->registerSubCommand(new editKitsubCommand($this->plugin, "edit", "Edit a kit", ["e", "ed"]));
        $this->registerSubCommand(new reloadKitsubCommand($this->plugin, "reload", "Reload kits", ["r", "rl"]));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!($sender instanceof Player)) {
            return;
        }
        KitManager::getInstance()->UIindex($sender);
    }

    public function getPermission(): string
    {
        return "kits.command.kit";
    }
}
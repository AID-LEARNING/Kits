<?php

namespace SenseiTarzan\Kits\Commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Commands\subCommands\RemoveWaitingPeriodSubCommand;
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
        $this->registerSubCommand(new RemoveWaitingPeriodSubCommand($this->plugin, "removeWaitingPeriod", "Remove waiting period of a kit", ["delWaitingPeriod", "removeWaiting", "delWaiting"]));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!($sender instanceof Player)) {
            return;
        }
        KitManager::getInstance()->UIindex($sender);
    }

    public function getPermission()
    {
        return "kits.command.kit";
    }
}
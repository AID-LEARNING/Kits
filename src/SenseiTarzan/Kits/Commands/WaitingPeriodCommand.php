<?php

namespace SenseiTarzan\Kits\Commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use SenseiTarzan\Kits\Commands\subCommands\RemoveWaitingPeriodSubCommand;

class WaitingPeriodCommand extends BaseCommand
{

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->setPermission($this->getPermission());
        $this->registerSubCommand(new RemoveWaitingPeriodSubCommand($this->plugin, "del", "Remove waiting period of a kit", ["delete", "remove", "rm"]));
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    public function getPermission(): string
    {
        return "kits.command.kit-wp";
    }
}
<?php

/*
 *
 *            _____ _____         _      ______          _____  _   _ _____ _   _  _____
 *      /\   |_   _|  __ \       | |    |  ____|   /\   |  __ \| \ | |_   _| \ | |/ ____|
 *     /  \    | | | |  | |______| |    | |__     /  \  | |__) |  \| | | | |  \| | |  __
 *    / /\ \   | | | |  | |______| |    |  __|   / /\ \ |  _  /| . ` | | | | . ` | | |_ |
 *   / ____ \ _| |_| |__| |      | |____| |____ / ____ \| | \ \| |\  |_| |_| |\  | |__| |
 *  /_/    \_\_____|_____/       |______|______/_/    \_\_|  \_\_| \_|_____|_| \_|\_____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author AID-LEARNING
 * @link https://github.com/AID-LEARNING
 *
 */

declare(strict_types=1);

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
	protected function prepare() : void
	{
		$this->setPermission("kits.command.kit");
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->registerSubCommand(new createKitSubCommand($this->plugin, "create", "Create a kit", ["c", "cr", "make"]));
		$this->registerSubCommand(new editKitsubCommand($this->plugin, "edit", "Edit a kit", ["e", "ed"]));
		$this->registerSubCommand(new reloadKitsubCommand($this->plugin, "reload", "Reload kits", ["r", "rl"]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
	{
		if (!($sender instanceof Player)) {
			return;
		}
		KitManager::getInstance()->UIindex($sender);
	}
}

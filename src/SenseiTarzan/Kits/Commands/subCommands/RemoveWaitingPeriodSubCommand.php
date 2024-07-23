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

namespace SenseiTarzan\Kits\Commands\subCommands;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use SenseiTarzan\Kits\Commands\args\KitListArgument;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SOFe\AwaitGenerator\Await;

class RemoveWaitingPeriodSubCommand extends BaseSubCommand
{

	/**
	 * @inheritDoc
	 */
	protected function prepare() : void
	{
		$this->setPermission("kits.command.kit-wp.remove");
		$this->registerArgument(0, new TargetPlayerArgument(false, "player"));
		$this->registerArgument(1, new KitListArgument("kit", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void
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

		Await::g2c($kitsPlayer->removeWaitingPeriod($kit->getId()), function () use ($sender, $player, $kit){
			$sender->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($sender, CustomKnownTranslationFactory::success_remove_waiting_period($player, $kit)));
		}, fn () => null);

	}
}

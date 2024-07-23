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

namespace SenseiTarzan\Kits\Component;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Kits\Class\Kits\KitsPlayer;
use function strtolower;

class KitsPlayerManager
{
	use SingletonTrait;

	/** @var KitsPlayer[] */
	private array $players = [];

	public function loadPlayer(KitsPlayer $kitsPlayer) : void
	{
		$this->players[$kitsPlayer->getId()] = $kitsPlayer;
	}

	public function unloadPlayer(Player $player) : void
	{
		unset($this->players[strtolower($player->getName())]);
	}

	public function getPlayer(Player|string $player) : ?KitsPlayer
	{
		return $this->players[strtolower($player instanceof Player ? $player->getName() : $player)] ?? null;
	}
}

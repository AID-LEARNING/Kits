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

namespace SenseiTarzan\Kits\Class\Save;

use Generator;
use pocketmine\player\Player;
use SenseiTarzan\DataBase\Class\IDataSave;
use SenseiTarzan\Kits\Class\Kits\KitsPlayer;
use SenseiTarzan\Kits\Class\Kits\WaitingPeriod;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SOFe\AwaitGenerator\Await;

abstract class IDataSaveKit implements IDataSave
{

	/**
	 * @return Generator<array<string, float>>
	 */
	abstract public function createPromiseInitializeKitsPlayer(Player|string $player) : Generator;

	abstract public function createPromiseUpdate(string $id, string $type, WaitingPeriod|array|string|null $data) : Generator;

	final public function loadDataPlayer(Player|string $player) : void
	{
		Await::g2c($this->createPromiseInitializeKitsPlayer($player), function (array $data) use ($player){
			KitsPlayerManager::getInstance()->loadPlayer(KitsPlayer::create($player, $data));
		}, function (\Exception|\Error $error) use ($player){
			$player->kick($error->getMessage());
		});
	}

	final public function loadDataPlayerByMiddleware(Player|string $player) : Generator
	{
		return Await::promise(function ($resolve) use ($player){
			Await::f2c(function () use ($player){
			$data = yield from $this->createPromiseInitializeKitsPlayer($player);
			KitsPlayerManager::getInstance()->loadPlayer(KitsPlayer::create($player, $data));
			return null;
		}, $resolve, $resolve);
		});
	}

	final public function updateOffline(string $id, string $type, mixed $data) : null
	{
		return null;
	}

	/**
	 * @param string                          $type "addWaitingPeriod" | "removeWaitingPeriod" | "clearWaitingPeriod"
	 * @param WaitingPeriod|array|string|null $data
	 */
	final public function updateOnline(string $id, string $type, mixed $data) : Generator
	{
		return $this->createPromiseUpdate($id, $type, $data);
	}

}

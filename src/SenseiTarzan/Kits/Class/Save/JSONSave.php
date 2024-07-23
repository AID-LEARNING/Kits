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
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\Kits\Class\Exception\SaveDataException;
use SenseiTarzan\Kits\Class\Kits\WaitingPeriod;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use function strtolower;

class JSONSave extends IDataSaveKit
{

	private Config $config;

	public function __construct(string $dataFolder)
	{
		$this->config = new Config(Path::join($dataFolder, "data.json"), Config::JSON);
	}

	public function getName() : string
	{
		return "JSON";
	}

	public function createPromiseInitializeKitsPlayer(Player|string $player) : Generator
	{
		return Await::promise(function ($resolve) use($player) : void {
			$resolve($this->config->get(($player instanceof Player ? $player->getName() : $player), []));
		});
	}

	public function createPromiseUpdate(string $id, string $type, WaitingPeriod|array|string|null $data) : Generator
	{
		return Await::promise(function ($resolve, $reject) use($id, $type, $data) : void{
			try {
				if ($type === "addWaitingPeriod") {
					if (!$data instanceof WaitingPeriod) return;
					$type = $id . "." . strtolower($data->getName());
					$data = $data->getPeriod();
				} elseif ($type === "removeWaitingPeriod") {
					$type = $id . "." . strtolower($data);
					$data = null;
				} elseif ($type === "clearWaitingPeriod") {
					$type = "$id";
					$data = [];
				}
				$this->config->setNested("$type", $data);
				$this->config->save();
				$resolve();
			}catch (JsonException){
				$reject(new SaveDataException("Error Update data of $id action $type"));
			}
		});
	}
}

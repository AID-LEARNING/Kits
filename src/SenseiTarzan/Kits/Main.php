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

namespace SenseiTarzan\Kits;

use CortexPE\Commando\PacketHooker;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\Kits\Class\Middleware\KitMiddleware;
use SenseiTarzan\Kits\Class\Save\JSONSave;
use SenseiTarzan\Kits\Class\Save\YAMLSave;
use SenseiTarzan\Kits\Commands\KitCommand;
use SenseiTarzan\Kits\Commands\WaitingPeriodCommand;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Listener\PlayerListener;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Middleware\Component\MiddlewareManager;
use SenseiTarzan\Path\PathScanner;
use Symfony\Component\Filesystem\Path;
use function dirname;
use function file_exists;
use function str_replace;

class Main extends PluginBase
{

	protected function onLoad() : void
	{
		if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
			foreach (PathScanner::scanDirectoryGenerator($search = Path::join(dirname(__DIR__,3) , "resources")) as $file){
				@$this->saveResource(str_replace($search, "", $file));
			}
		}
		new LanguageManager($this);
		$typeSave = $this->getConfig()->get("type-save");
		match ($typeSave) {
			"yaml" => DataManager::getInstance()->setDataSystem(new YAMLSave($this->getDataFolder())),
			"json" => DataManager::getInstance()->setDataSystem(new JSONSave($this->getDataFolder())),
			default => null
		};
	}

	protected function onEnable() : void
	{
		if (!PacketHooker::isRegistered())
			PacketHooker::register($this);
		if (!InvMenuHandler::isRegistered())
			InvMenuHandler::register($this);
		$this->getScheduler()->scheduleTask(new ClosureTask(function () {
			if (DataManager::getInstance()->getDataSystem() === null)
				$this->getLogger()->alert("no DataSystem selected");
			new KitManager($this);
		}));
		$hasMiddleware = $this->getServer()->getPluginManager()->getPlugin("Middleware") !== null;
		if ($hasMiddleware)
			MiddlewareManager::getInstance()->addMiddleware(new KitMiddleware());
		EventLoader::loadEventWithClass($this, new PlayerListener($hasMiddleware));
		LanguageManager::getInstance()->loadCommands("kits");
		$this->getServer()->getCommandMap()->registerAll("kits", [
			new KitCommand($this, "kit", "Kits command", ["kits"]),
			new WaitingPeriodCommand($this, "kits-wp", "WaitingPeriod command", ["kit-wp"])
		]);
	}

}

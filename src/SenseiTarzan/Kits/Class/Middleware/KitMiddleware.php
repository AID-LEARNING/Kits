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

namespace SenseiTarzan\Kits\Class\Middleware;

use Generator;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use SenseiTarzan\Kits\Main;
use SenseiTarzan\Middleware\Class\AttributeMiddlewarePriority;
use SenseiTarzan\Middleware\Class\IMiddleWare;
use SenseiTarzan\Middleware\Class\MiddlewarePriority;

#[AttributeMiddlewarePriority(MiddlewarePriority::MONITOR)]
class KitMiddleware implements IMiddleWare
{

	public function getName() : string
	{
		return "Kit Middleware";
	}

	/**
	 * @inheritDoc
	 */
	public function onDetectPacket() : string
	{
		return SetLocalPlayerAsInitializedPacket::class;
	}

	public function getPromise(DataPacketReceiveEvent $event) : Generator
	{
		return Main::getInstance()->getDataManager()->getDataSystem()->loadDataPlayerByMiddleware($event->getOrigin()->getPlayer());
	}
}

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

namespace SenseiTarzan\Kits\Listener;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemBlock;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\Kits\Main;

readonly class PlayerListener
{

	public function __construct(private bool $hasMiddleware) {}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onJoin(PlayerJoinEvent $event) : void
	{
		if (!$this->hasMiddleware)
			Main::getInstance()->getDataManager()->getDataSystem()->loadDataPlayer($event->getPlayer());
	}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onQuit(PlayerQuitEvent $event) : void
	{
		KitsPlayerManager::getInstance()->unloadPlayer($event->getPlayer());
	}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onClick(PlayerInteractEvent $event) : void
	{
		if ($event->isCancelled()) return;
		if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$player = $event->getPlayer();
			$item = $event->getItem();
			if ($item instanceof ItemBlock) {
				if ($item->getBlock()->getTypeId() === BlockTypeIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
					if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
						$player->getInventory()->removeItem($item->setCount(1));
						$event->cancel();

					}
				}
			}
		}
	}

	private function onHand(InventoryTransactionEvent $event) : void
	{
		$player = $event->getTransaction()->getSource();
		foreach ($event->getTransaction()->getActions() as $action) {
			if (InvMenuHandler::getPlayerManager()->getNullable($player) !== null) continue;
			if (!$action->getSourceItem()->getNamedTag()->getByte("illegal", 0)) {
				$event->cancel();
				$player->getInventory()->removeItem($action->getSourceItem());
			}
			if (!$action->getTargetItem()->getNamedTag()->getByte("illegal", 0)) {
				$event->cancel();
				$player->getCursorInventory()->removeItem($action->getTargetItem());
			}
		}
	}

	#[EventAttribute(EventPriority::LOWEST)]
	public function onUse(PlayerItemUseEvent $event) : void
	{
		if ($event->isCancelled()) return;
		$player = $event->getPlayer();
		$item = $event->getItem();
		if ($item instanceof ItemBlock) {
			if ($item->getBlock()->getTypeId() === BlockTypeIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
				if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
					$player->getInventory()->removeItem($item->setCount(1));
					$event->cancel();
				}
			}
		}
	}
}

<?php

namespace SenseiTarzan\Kits\Listener;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemIds;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Component\KitsPlayerManager;

class PlayerListener
{

    #[EventAttribute(EventPriority::LOWEST)]
    public function onJoin(PlayerJoinEvent $event): void
    {
        DataManager::getInstance()->getDataSystem()->loadDataPlayer($event->getPlayer());
    }


    #[EventAttribute(EventPriority::LOWEST)]
    public function onQuit(PlayerQuitEvent $event): void
    {
        KitsPlayerManager::getInstance()->unloadPlayer($event->getPlayer());
    }

    #[EventAttribute(EventPriority::LOWEST)]
    public function onClick(PlayerInteractEvent $event): void
    {
        if ($event->isCancelled()) return;
        if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if ($item instanceof ItemBlock) {
                if ($item->getBlock()->getId() === BlockLegacyIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
                    if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
                        $player->getInventory()->removeItem($item->setCount(1));
                        $event->cancel();

                    }
                }
            }
        }
    }

    #[EventAttribute(EventPriority::LOWEST)]
    public function onUse(PlayerItemUseEvent $event): void
    {
        if ($event->isCancelled()) return;
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item instanceof ItemBlock) {
            if ($item->getBlock()->getId() === BlockLegacyIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
                if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
                    $player->getInventory()->removeItem($item->setCount(1));
                    $event->cancel();
                }
            }
        }
    }
}
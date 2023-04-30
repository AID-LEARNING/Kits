<?php

namespace SenseiTarzan\Kits\Listener;

use pocketmine\block\BlockTypeIds;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemBlock;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\Kits\libs\muqsit\invmenu\InvMenuHandler;

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
                if ($item->getBlock()->getTypeId() === BlockTypeIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
                    if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
                        $player->getInventory()->removeItem($item->setCount(1));
                        $event->cancel();

                    }
                }
            }
        }
    }

    private function onHand(InventoryTransactionEvent $event): void
    {
        $player = $event->getTransaction()->getSource();
        foreach ($event->getTransaction()->getActions() as $action) {
            if (InvMenuHandler::getPlayerManager()->getNullable($player) !== null) continue;
            if (!$action->getSourceItem()->getNamedTag()->getByte("illegal", false)) {
                $event->cancel();
                $player->getInventory()->removeItem($action->getSourceItem());
            }
            if (!$action->getTargetItem()->getNamedTag()->getByte("illegal", false)) {
                $event->cancel();
                $player->getCursorInventory()->removeItem($action->getTargetItem());
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
            if ($item->getBlock()->getTypeId() === BlockTypeIds::CHEST && $item->getNamedTag()->getTag("kit") !== null) {
                if (KitManager::getInstance()->giveKitToPlayer($player, $item->getNamedTag()->getString("kit", Kit::DEFAULT_STRING_TAG))) {
                    $player->getInventory()->removeItem($item->setCount(1));
                    $event->cancel();
                }
            }
        }
    }
}
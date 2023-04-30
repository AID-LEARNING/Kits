<?php

namespace SenseiTarzan\Kits\Utils;

use Exception;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\LegacyItemIdToStringIdMap;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\InvalidItemStateException;
use pocketmine\network\mcpe\convert\UnsupportedItemException;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use SenseiTarzan\Kits\Class\Kits\WaitingPeriod;

class Convertor
{

    public static function jsonToWaitingPeriod(array $json): array
    {
        foreach ($json as $name => $period) {
            $json[$name] = new WaitingPeriod($name, floatval($period));
        }
        return $json;
    }

    public static function jsonToItem(array $info): Item
    {
        if (is_numeric($info['id'])) {
                $item = Item::jsonDeserialize($info);
        } else {
            $item = self::upgradeItemJSON($info);
        }
        if (LegacyItemIdToStringIdMap::getInstance()->legacyToString($item->getId()) === null) {
            $item = clone VanillaBlocks::INFO_UPDATE()->asItem()->setCustomName(TextFormat::DARK_RED . TextFormat::BOLD . "Error Item " . $info['id'] . ":" . ($info["damage"] ?? 0) . TextFormat::RESET . TextFormat::RED . " not found");
        }else {
            if (isset($info['customName'])) {
                $item->setCustomName($info['customName']);
            }
        }


        if (isset($info['enchant'])) {
            foreach ($info['enchant'] as $id => $lvl) {
                $enchant = EnchantmentIdMap::getInstance()->fromId($id);
                if ($enchant === null) continue;
                $item->addEnchantment(new EnchantmentInstance($enchant, $lvl));
            }
        }

        if (isset($info['lore'])) {
            $item->setLore($info['lore']);
        }

        return $item;
    }

    public static function jsonToItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = self::jsonToItem($item);
        }
        return $result;
    }

    /**
     * @param Item[] $getItems
     * @return array
     */
    public static function itemsToJson(array $getItems): array
    {
        $result = [];
        foreach ($getItems as $item) {
            $result[] = self::itemToJson($item);
        }
        return $result;
    }

    /**
     * @param Item $item
     * @return array
     */
    private static function itemToJson(Item $item): array
    {
        return ['id' => $item->getId(), "damage" => $item instanceof Durable ? $item->getDamage() : $item->getMeta(), "count" => $item->getCount(), "customName" => $item->getCustomName(), "enchant" => self::enchantToJson($item), "lore" => $item->getLore()];
    }


    /**
     * @param array $info
     * @return Item
     * @throws SavedDataLoadingException
     */
    private static function upgradeItemJSON(array $info): Item
    {
        $info["id"] = LegacyItemIdToStringIdMap::getInstance()->stringToLegacy($info["id"]) ?? PHP_INT_MAX;
        return Item::jsonDeserialize($info);
    }


    private static function enchantToJson(Item $item): array
    {
        $enchant = [];
        foreach ($item->getEnchantments() as $enchantment) {
            $enchant[EnchantmentIdMap::getInstance()->toId($enchantment->getType())] = $enchantment->getLevel();
        }
        return $enchant;
    }

}
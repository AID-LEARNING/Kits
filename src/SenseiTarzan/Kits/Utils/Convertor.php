<?php

namespace SenseiTarzan\Kits\Utils;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
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
        try {
            $item = Item::jsonDeserialize($info);

            if (isset($info['customName'])) {
                $item->setCustomName($info['customName']);
            }
        } catch (SavedDataLoadingException) {
            $item = VanillaBlocks::INFO_UPDATE()->asItem()->setCustomName(TextFormat::DARK_RED . TextFormat::BOLD . "Error Item " . $info['id'] . ":" . ($info["damage"] ?? 0) . TextFormat::RESET . TextFormat::RED . " not found");
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
}
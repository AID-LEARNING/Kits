<?php

namespace SenseiTarzan\Kits\Utils;

use Exception;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
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
            try {
                $item = Item::legacyJsonDeserialize($info);
                if (isset($info['customName'])) {
                    $item->setCustomName($info['customName']);
                }
            } catch (Exception) {
                $item = clone VanillaBlocks::INFO_UPDATE()->asItem()->setCustomName(TextFormat::DARK_RED . TextFormat::BOLD . "Error Item " . $info['id'] . ":" . ($info["damage"] ?? 0) . TextFormat::RESET . TextFormat::RED . " not found");
            }
        } else {
            try {
                $item = self::upgradeItemJSON($info);
                if (isset($info['customName'])) {
                    $item->setCustomName($info['customName']);
                }
            } catch (Exception) {
                $item = clone VanillaBlocks::INFO_UPDATE()->asItem()->setCustomName(TextFormat::DARK_RED . TextFormat::BOLD . "Error Item " . $info['id'] . ":" . ($info["damage"] ?? 0) . TextFormat::RESET . TextFormat::RED . " not found");
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
        $serialized = GlobalItemDataHandlers::getSerializer()->serializeType($item);
        return ['id' => $serialized->getName(), "damage" => $item instanceof Durable ? $item->getDamage() : $serialized->getMeta(), "count" => $item->getCount(), "customName" => $item->getCustomName(), "enchant" => self::enchantToJson($item), "lore" => $item->getLore()];
    }


    /**
     * @param array $info
     * @return Item
     * @throws SavedDataLoadingException
     */
    private static function upgradeItemJSON(array $info): Item
    {
        $nbt = "";

        //Backwards compatibility
        if (isset($data["nbt"])) {
            $nbt = $data["nbt"];
        } elseif (isset($data["nbt_hex"])) {
            $nbt = hex2bin($data["nbt_hex"]);
        } elseif (isset($data["nbt_b64"])) {
            $nbt = base64_decode($data["nbt_b64"], true);
        }
        $itemStackData = GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataString($info['id'], $info['damage'] ?? 0, $info['count'] ?? 1,
            $nbt !== "" ? (new LittleEndianNbtSerializer())->read($nbt)->mustGetCompoundTag() : null
        );

        try {
            return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
        } catch (ItemTypeDeserializeException $e) {
            throw new SavedDataLoadingException($e->getMessage(), 0, $e);
        }
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
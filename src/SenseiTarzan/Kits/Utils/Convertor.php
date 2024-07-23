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

namespace SenseiTarzan\Kits\Utils;

use Exception;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use SenseiTarzan\Kits\Class\Kits\WaitingPeriod;
use function base64_decode;
use function base64_encode;
use function floatval;
use function hex2bin;
use function is_numeric;

class Convertor
{

	public static function jsonToWaitingPeriod(array $json) : array
	{
		foreach ($json as $name => $period) {
			$json[$name] = new WaitingPeriod($name, floatval($period));
		}
		return $json;
	}

	public static function jsonToItem(array $info) : Item
	{
		if (!isset($info['id'])){
			return VanillaBlocks::AIR()->asItem();
		}
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
			} catch (Exception $exception) {
				Server::getInstance()->getLogger()->logException($exception, $exception->getTrace());
				$item = clone VanillaBlocks::INFO_UPDATE()->asItem()->setCustomName(TextFormat::DARK_RED . TextFormat::BOLD . "Error Item " . $info['id'] . ":" . ($info["damage"] ?? 0) . TextFormat::RESET . TextFormat::RED . " not found");
			}
		}

		if (isset($info['enchant'])) {
			$item->removeEnchantments();
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

	public static function jsonToItems(array $items) : array
	{
		$result = [];
		foreach ($items as $item) {
			$result[] = self::jsonToItem($item);
		}
		return $result;
	}

	/**
	 * @param Item[] $getItems
	 */
	public static function itemsToJson(array $getItems) : array
	{
		$result = [];
		foreach ($getItems as $item) {
			$result[] = self::itemToJson($item);
		}
		return $result;
	}

	private static function itemToJson(Item $item) : array
	{
		$serialized = GlobalItemDataHandlers::getSerializer()->serializeType($item);
		$data = ['id' => $serialized->getName(), "damage" => $item instanceof Durable ? $item->getDamage() : $serialized->getMeta(), "count" => $item->getCount()];
		if (($nbt = $item->getNamedTag())->count() !== 0){
			$data['nbt_b64'] = base64_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)));
		}
		if($serialized->getBlock() != null){
			$nbt = CompoundTag::create();
			foreach ($serialized->getBlock()->getStates() as $name => $tag) {
				$nbt->setTag($name, $tag);
			}
			$data["damage"] = 0;
			$data['block_states'] = base64_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)));
		}
		return $data;
	}

	private static function StringItemJson(array $info) : ?Item
	{
		$name = $info['id'];
		$meta = $info['damage'] ?? 0;
		$blockName = BlockItemIdMap::getInstance()->lookupBlockId($name);
		$blockStateData = null;
		if ($blockName !== null) {
			if ($meta !== 0) {
				throw new SavedDataLoadingException("Meta should not be specified for blockitems");
			}
			$blockStatesRaw = $info['block_states'] ?? null;
			$blockStatesTag = $blockStatesRaw === null ?
				[] :
				(new LittleEndianNbtSerializer())
					->read(base64_decode($blockStatesRaw, true))
					->mustGetCompoundTag()
					->getValue();
			$blockStateData = BlockStateData::current($blockName, $blockStatesTag);
		}
		$nbtRaw = null;
		if (isset($info["nbt"])) {
			$nbtRaw = $info["nbt"];
		} elseif (isset($info["nbt_hex"])) {
			$nbtRaw = hex2bin($info["nbt_hex"]);
		} elseif (isset($info["nbt_b64"])) {
			$nbtRaw = base64_decode($info["nbt_b64"], true);
		}
		$nbt = $nbtRaw === null ? null : (new LittleEndianNbtSerializer())
			->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($nbtRaw, true)))
			->mustGetCompoundTag();

		$count = $info['count'] ?? 1;
		$canPlaceOn = $info['can_place_on'] ?? [];
		$canDestroy = $info['can_destroy'] ?? [];
		$itemStackData = new SavedItemStackData(
			new SavedItemData(
				$name,
				$meta,
				$blockStateData,
				$nbt
			),
			$count,
			null,
			null,
			$canPlaceOn,
			$canDestroy
		);

		try {
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		} catch (ItemTypeDeserializeException) {
			//probably unknown item
			return null;
		}
	}

	/**
	 * @throws SavedDataLoadingException
	 */
	private static function upgradeItemJSON(array $info) : Item
	{
		if (($item = self::StringItemJson($info)) != null)
			return $item;
		$nbt = "";
		//Backwards compatibility
		if (isset($data["nbt"])) {
			$nbt = $data["nbt"];
		} elseif (isset($data["nbt_hex"])) {
			$nbt = hex2bin($data["nbt_hex"]);
		} elseif (isset($data["nbt_b64"])) {
			$nbt = base64_decode($data["nbt_b64"], true);
		}
		try {
			$itemStackData = GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataString($info['id'], $info['damage'] ?? 0, $info['count'] ?? 1,
				$nbt !== "" ? (new LittleEndianNbtSerializer())->read($nbt)->mustGetCompoundTag() : null
			);
			$item = GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch (\Throwable $e){
			$item = StringToItemParser::getInstance()->parse($info['id']) ?? LegacyStringToItemParser::getInstance()->parse($info['id']);
			if (!$item) {
				throw new SavedDataLoadingException("item : " . $info['id'] . ":" . ($info['damage'] ?? 0), 0, $e);
			}
			$item->setCount(($info['count'] ?? 1));
			$item->setNamedTag((new LittleEndianNbtSerializer())->read($nbt)->mustGetCompoundTag());
		} finally {
			if (!$item) {
				throw new SavedDataLoadingException("item : " . $info['id'] . ":" . ($info['damage'] ?? 0), 0, null);
			}
			return $item;
		}
	}
}

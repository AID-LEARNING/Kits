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

namespace SenseiTarzan\Kits\Class\Kits;

use JsonSerializable;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\Kits\Commands\args\KitListArgument;
use SenseiTarzan\Kits\Utils\Convertor;
use SenseiTarzan\Kits\Utils\Format;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

class Kit implements JsonSerializable
{

	const DEFAULT_STRING_TAG = "2aa38484-6e72-4e91-943f-838905a7a995";

	private string $id;
	/** @var Item[] */
	private array $items = [];

	public function __construct(private Config $config, private string $name, private IconForm $iconForm, private string $descriptionPath, private string $description, private string $permission, private float $delay, array $items)
	{
		$this->id = Format::nameToId($name);
		if ($this->descriptionPath !== null) {
			foreach (LanguageManager::getInstance()->getAllLang() as $language) {
				$config = $language->getConfig();
				if ($config->getNested($this->descriptionPath) !== null) continue;
				$config->setNested($this->descriptionPath, $this->getDescriptionRaw());
				$config->save();
			}
		}
		KitListArgument::$VALUES[$this->getId()] = $name;
		if (PermissionManager::getInstance()->getPermission($this->permission) === null) {
			PermissionManager::getInstance()->addPermission(new Permission($this->permission, "$name kit permission"));
			PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)->addChild($this->permission, true);

		}
		$this->items = Convertor::jsonToItems($items);
	}

	public static function create(Config $config, string $name, string $image, ?string $descriptionPath, string $description, string $permission, float $delay, array $items) : Kit
	{
		return new self($config, $name, IconForm::create($image), $descriptionPath, $description, $permission, $delay, $items);
	}

	public function getConfig() : Config
	{
		return $this->config;
	}

	public function getId() : string
	{
		return $this->id;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getDescription(CommandSender|string|null $player = null) : string
	{
		return $player === null ? $this->getDescriptionRaw() :  LanguageManager::getInstance()->getTranslate($player, $this->descriptionPath, [], $this->getDescriptionRaw());
	}

	public function getDescriptionPath() : string
	{
		return $this->descriptionPath;
	}

	public function getDescriptionRaw() : string
	{
		return $this->description;
	}

	public function getIconForm() : IconForm
	{
		return $this->iconForm;
	}

	public function getPermission() : string
	{
		return $this->permission;
	}

	/**
	 * the delay is in seconds
	 */
	public function getDelay() : float
	{
		return $this->delay;
	}

	/**
	 * @return Item[]
	 */
	public function getItems() : array
	{
		return $this->items;
	}

	public function hasFreePlace(Player $player) : bool
	{
		$inv = $player->getInventory();

		foreach ($this->getItems() as $item) {
			if (!$inv->canAddItem($item)) {
				return false;
			}
		}

		return true;
	}

	public function hasPermission(Player $player) : bool
	{
		return $player->hasPermission($this->getPermission());
	}

	public function setDescription(string $description) : void
	{
		$this->description = $description;
	}

	public function setDelay(float $delay) : void
	{
		$this->delay = $delay;
	}

	public function setIconForm(string $iconForm) : void
	{
		$this->iconForm = IconForm::create($iconForm);
	}

	public function setPermission(string $permission) : void
	{
		$this->permission = $permission;
	}

	/**
	 * @param Item[] $items
	 */
	public function setItems(array $items) : void
	{
		$this->items = $items;
	}

	public function save() : void
	{
		$this->getConfig()->setAll($this->jsonSerialize());
		$this->getConfig()->save();
	}

	public function jsonSerialize() : array
	{
		return [
			"name" => $this->getName(),
			"image" => $this->getIconForm()->getPath(),
			"description" => $this->getDescription(),
			"permission" => $this->getPermission(),
			"delay" => $this->getDelay(),
			"items" => Convertor::itemsToJson($this->getItems())
		];
	}

	public function __destruct()
	{
		unset(KitListArgument::$VALUES[$this->getId()]);
		if (PermissionManager::getInstance()->getPermission($this->permission) !== null) {
			PermissionManager::getInstance()->removePermission($this->permission);
			PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)->removeChild($this->permission);
		}
	}
}

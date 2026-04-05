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

namespace SenseiTarzan\Kits\Component;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Main;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\Kits\Utils\Format;
use SenseiTarzan\Path\PathScanner;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use function array_filter;
use function file_exists;
use function floatval;
use function is_array;
use function unlink;

class KitManager
{
	use SingletonTrait;

	/** @var Kit[] */
	private array $kits = [];
	private Config $config;

	public function __construct(public Main $plugin)
	{
		self::setInstance($this);
		$this->config = $this->plugin->getConfig();
		$this->load();
	}

	/**
	 * @throws \JsonException
	 */
	public function load() : void
	{
		foreach (PathScanner::scanDirectoryToConfig(Path::join($this->plugin->getDataFolder(), "Kits"), ['yml']) as $config) {
			if (is_array($description = $config->get("description"))) {
				$config->set("description", $description = $description["default"] ?? "Description not found");
				$config->save();
			}
			$kit = Kit::create($config, $name = $config->get('name'), $config->get("image"), "Kit.description." . Format::nameToId($name), $description, $config->get("permission"), floatval($config->get("delay", -1)), $config->get("items"));
			$this->kits[$kit->getId()] = $kit;
		}
	}

	/**
	 * @throws \JsonException
	 */
	private function createKit(string $name, string $image, string $description, string $permission, float $delay, array $items) : Kit
	{
		$config = new Config(Path::join($this->plugin->getDataFolder(), "Kits", Format::nameToId($name) . ".kit.yml"), Config::YAML);
		$kit = Kit::create($config, $name, $image, "Kit.description." . Format::nameToId($name), $description, $permission, $delay, $items);
		$config->setAll($kit->jsonSerialize());
		$config->save();
		$this->kits[$kit->getId()] = $kit;
		return $kit;
	}

	/**
	 * Allows to update the kit but also to be sure that all items have been found
	 */
	public function reload() : void
	{
		unset($this->kits);
		$this->kits = [];
		$this->load();
	}

	public function getKit(string $name) : ?Kit
	{
		return $this->kits[Format::nameToId($name)] ?? null;
	}

	public function existKit(string $name) : bool
	{
		return isset($this->kits[$name]);
	}

	/**
	 * @return Kit[]
	 */
	public function getKits() : array
	{
		return $this->kits;
	}

	public function UIindex(Player $player) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $index) : void {
			if ($index === null) return;
			$kit = $this->getKit($index);
			if ($kit === null) return;
			$this->UIKitRecovery($player, $kit);
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_index()));

		foreach ($this->getKits() as $kit) {
			if (!$kit->hasPermission($player)) continue;
			$ui->addButton($kit->getName(), $kit->getIconForm()->getType(), $kit->getIconForm()->getPath(), $kit->getId());
		}
		$player->sendForm($ui);
	}

	public function UIKitRecovery(Player $player, Kit $kit) : void
	{
		$ui = new SimpleForm(function (Player $player, ?int $index) use ($kit) : void {
			if ($index === 0) {
				$target = KitsPlayerManager::getInstance()->getPlayer($player);
				if (!$kit->hasPermission($player)) {
					$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_have_permissions()));
					return;
				}
				if ($kit->getDelay() > 0) {
					if (!$target->canRetrieveKit($kit->getId())) {
						$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::get_waiting_period($kit->getName(), $target->getWaitingPeriod($kit->getId())?->getPeriod() ?? 0.0)));
						return;
					}
				}
				$chest = VanillaBlocks::CHEST()->asItem()
					->setCustomName("{$kit->getName()} Kit");
				$chest->getNamedTag()->setString("kit", $kit->getId());

				if (!$player->getInventory()->canAddItem($chest)) {
					$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_free_place()));
					return;
				}
				Await::g2c(KitsPlayerManager::getInstance()->getPlayer($player)->addWaitingPeriod($kit->getId(), $kit->getDelay()),
				function () use($player, $chest, $kit){
					if ($kit->getDelay() > 0) {
						$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::add_waiting_period($kit->getName(), $kit->getDelay())));
					}
					$player->getInventory()->addItem($chest);
					$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_get_kit($kit->getName())));
				});
			} else {
				$this->UIindex($player);
			}
		});

		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_information($kit->getName())));
		$ui->setContent($kit->getDescription($player));
		$ui->addButton(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::accepted_button()));
		$ui->addButton(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::cancel_button()));
		$player->sendForm($ui);
	}

	public function giveKitToPlayer(Player $player, string $kitName) : bool
	{
		if ($kitName === Kit::DEFAULT_STRING_TAG) return false;
		$target = KitsPlayerManager::getInstance()->getPlayer($player);
		if ($target === null) {
			$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_not_found_kits_player()));
			return false;
		}
		$kit = $this->getKit($kitName);
		if ($kit === null) {
			$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_exist_kit($kitName)));
			return false;
		}
		if (!$kit->hasFreePlace($player)) {
			$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_free_place()));
			return false;
		}
		$player->getInventory()->addItem(...$kit->getItems());
		$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_open_kit($kit->getName())));
		return true;
	}

	public function UICreate(Player $player) : void
	{
		$ui = new CustomForm(function (Player $player, ?array $data) : void {
			$name = $data[0] ?? null;
			$description = $data[1] ?? null;
			if ($name === null || $description === null) return;
			$delay = floatval($data[2] ?? -1);
			$permission = $data[3] ?? "";
			$image = $data[4] ?? "";
			$kit = $this->createKit($name, $image, $description, $permission, $delay, []);
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $kit): void {
                $this->GUIEditOrCreateKitItems($player, $kit);
            }), 20);
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_create()));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_name()));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_description()));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_delay()));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_permission()));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_image()));
		$player->sendForm($ui);
	}

	public function UIEditIndex(Player $player) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $index) : void {
			if ($index === null) return;
			$kit = $this->getKit($index);
			if ($kit === null) return;
			$this->UIEditKit($player, $kit);
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_index()));

		foreach ($this->getKits() as $kit) {
			$ui->addButton($kit->getName(), $kit->getIconForm()->getType(), $kit->getIconForm()->getPath(), $kit->getId());
		}
		$player->sendForm($ui);
	}

	public function UIEditKit(Player $player, Kit $kit) : void
	{
		$ui = new SimpleForm(function (Player $player, ?int $data) use ($kit) : void {
			if ($data === null) {
				$this->UIEditIndex($player);
				return;
			}
			switch ($data) {
				case 0:
					$this->UIEditKitGeneralInfo($player, $kit);
					break;
				case 1:
					Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $kit): void {
                        $this->GUIEditOrCreateKitItems($player, $kit, true);
                    }), 20);
					break;
				case 2:
					$this->UIConfirmeRemoveKit($player, $kit);
					break;
			}
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
		$ui->addButton(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_general_information()));
		$ui->addButton(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_items()));
		$ui->addButton(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_remove()));
		$player->sendForm($ui);
	}

	public function UIEditKitGeneralInfo(Player $player, Kit $kit) : void
	{
		$ui = new CustomForm(function (Player $player, ?array $data) use ($kit) : void {
			if ($data === null) return;
			$kit->setIconForm($data[1]);
			$kit->setPermission($data[2]);
			$kit->setDelay($data[3]);
			$kit->save();
			$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_save_kit($kit)));
			$this->UIEditKit($player, $kit);
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_name()), $kit->getName(), $kit->getName());
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_image()), $kit->getIconForm()->getPath(), $kit->getIconForm()->getPath());
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_permission()), $kit->getPermission(), $kit->getPermission());
		$ui->addInput(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_delay()), (string) $kit->getDelay(), (string) $kit->getDelay());

		$player->sendForm($ui);
	}

	private function GUIEditOrCreateKitItems(Player $player, Kit $kit, bool $edit = false) : void
	{
		$chestMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
		$chestMenu->setName(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_gui($kit)));
		foreach ($kit->getItems() as $item) {
			$chestMenu->getInventory()->addItem($item);
		}
		$barrier = VanillaBlocks::BARRIER()->asItem()->setCustomName("Illegal slot");
		$barrier->getNamedTag()->setByte("illegal", 1);
		for ($i = 36; $i < 54; $i++) {
			$chestMenu->getInventory()->setItem($i, clone $barrier);
		}
		$chestMenu->setListener(function (InvMenuTransaction $transaction) use ($kit) : InvMenuTransactionResult {
			if ($transaction->getAction()->getSlot() >= 36 && $transaction->getAction()->getSlot() <= 54) {
				return $transaction->discard();
			}
			return $transaction->continue();
		});
		$chestMenu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($kit, $edit) : void {
			$kit->setItems(array_filter($inventory->getContents(), function (Item $item) : bool {
				return !$item->isNull() && !($item->getNamedTag()->getByte("illegal", 0) || $item->getTypeId() === -BlockTypeIds::BARRIER);
			}));
			$kit->save();
			$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_save_kit($kit)));
			if ($edit) $this->UIEditKit($player, $kit);

		});
		$chestMenu->send($player);

	}

	private function UIConfirmeRemoveKit(Player $player, Kit $kit) : void
	{
		$ui = new ModalForm(function (Player $player, ?bool $data) use ($kit) : void {
			if ($data === true) {
				$this->removeKit($kit);
				$player->sendMessage(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_delete_kit($kit)));
			}
			$this->UIEditIndex($player);
		});
		$ui->setTitle(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
		$ui->setContent(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::content_remove_kit($kit)));
		$ui->setButton1(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::accepted_button()));
		$ui->setButton2(Main::getInstance()->getLanguageManager()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::cancel_button()));
		$player->sendForm($ui);
	}

	/**
	 * @throws \JsonException
	 */
	private function removeKit(Kit $kit) : void
	{
		$configFile = $kit->getConfig()->getPath();
		if (file_exists($configFile)) {
			unlink($configFile);
		}
		unset($this->kits[$kit->getId()]);
		foreach (Main::getInstance()->getLanguageManager()->getAllLang() as $language) {
			$config = $language->getConfig();
			if ($config->getNested($kit->getDescriptionPath()) === null) continue;
			$config->removeNested($kit->getDescriptionPath());
			$config->save();
		}
	}
}

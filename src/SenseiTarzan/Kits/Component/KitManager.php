<?php

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
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Main;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\Kits\Utils\Format;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use Symfony\Component\Filesystem\Path;

class KitManager
{
    use SingletonTrait;

    /**
     * @var Kit[]
     */
    private array $kits = [];
    private Config $config;

    public function __construct(public Main $plugin)
    {
        self::setInstance($this);
        $this->config = $this->plugin->getConfig();
        $this->load();
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function load(): void
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
     * @param string $name
     * @param string $image
     * @param string $description
     * @param string $permission
     * @param float $delay
     * @param array $items
     * @return Kit
     * @throws \JsonException
     */
    private function createKit(string $name, string $image, string $description, string $permission, float $delay, array $items): Kit
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
     * @return void
     */
    public function reload(): void
    {
        unset($this->kits);
        $this->kits = [];
        $this->load();
    }

    /**
     * @param string $name
     * @return Kit
     */
    public function getKit(string $name): ?Kit
    {
        return $this->kits[Format::nameToId($name)] ?? null;
    }

    public function existKit(string $name): bool
    {
        return isset($this->kits[$name]);
    }

    /**
     * @return Kit[]
     */
    public function getKits(): array
    {
        return $this->kits;
    }

    public function UIindex(Player $player): void
    {
        $ui = new SimpleForm(function (Player $player, ?string $index): void {
            if ($index === null) return;
            $kit = $this->getKit($index);
            if ($kit === null) return;
            $this->UIKitRecovery($player, $kit);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_index()));

        foreach ($this->getKits() as $kit) {
            if (!$kit->hasPermission($player)) continue;
            $ui->addButton($kit->getName(), $kit->getIconForm()->getType(), $kit->getIconForm()->getPath(), $kit->getId());
        }
        $player->sendForm($ui);
    }

    public function UIKitRecovery(Player $player, Kit $kit): void
    {
        $ui = new SimpleForm(function (Player $player, ?int $index) use ($kit): void {
            if ($index === 0) {
                $target = KitsPlayerManager::getInstance()->getPlayer($player);
                if (!$kit->hasPermission($player)) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_have_permissions()));
                    return;
                }

                $hasDelay = $kit->getDelay() > 0;
                if ($hasDelay) {
                    if (!$target->canRetrieveKit($kit->getId())) {
                        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::get_waiting_period($kit->getName(), $target->getWaitingPeriod($kit->getId())?->getPeriod() ?? 0.0)));
                        return;
                    } else {
                        $target->removeWaitingPeriod($kit->getId());
                    }
                }
                $chest = VanillaBlocks::CHEST()->asItem()
                    ->setCustomName("{$kit->getName()} Kit");
                $chest->getNamedTag()->setString("kit", $kit->getId());

                if (!$player->getInventory()->canAddItem($chest)) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_free_place()));
                    return;
                }
                if ($hasDelay) {
                    KitsPlayerManager::getInstance()->getPlayer($player)->addWaitingPeriod($kit->getId(), $kit->getDelay());
                }
                $player->getInventory()->addItem($chest);
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_get_kit($kit->getName())));

            } else {
                $this->UIindex($player);
            }
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_information($kit->getName())));
        $ui->setContent($kit->getDescription($player));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::accepted_button()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::cancel_button()));
        $player->sendForm($ui);
    }

    public function giveKitToPlayer(Player $player, string $kitName): bool
    {
        if ($kitName === Kit::DEFAULT_STRING_TAG) return false;
        $target = KitsPlayerManager::getInstance()->getPlayer($player);
        if ($target === null) {
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_not_found_kits_player()));
            return false;
        }
        $kit = $this->getKit($kitName);
        if ($kit === null) {
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_exist_kit($kitName)));
            return false;
        }
        if (!$kit->hasFreePlace($player)) {
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_free_place()));
            return false;
        }
        $player->getInventory()->addItem(...$kit->getItems());
        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_open_kit($kit->getName())));
        return true;
    }

    public function UICreate(Player $player): void
    {
        $ui = new CustomForm(function (Player $player, ?array $data): void {
            $name = $data[0] ?? null;
            $description = $data[1] ?? null;
            if ($name === null || $description === null) return;
            $delay = floatval($data[2] ?? -1);
            $permission = $data[3] ?? "";
            $image = $data[4] ?? "";
            $kit = $this->createKit($name, $image, $description, $permission, $delay, []);
            $this->GUIEditOrCreateKitItems($player, $kit);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_create()));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_name()));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_description()));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_delay()));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_permission()));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_image()));
        $player->sendForm($ui);
    }

    public function UIEditIndex(Player $player): void
    {
        $ui = new SimpleForm(function (Player $player, ?string $index): void {
            if ($index === null) return;
            $kit = $this->getKit($index);
            if ($kit === null) return;
            $this->UIEditKit($player, $kit);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_index()));

        foreach ($this->getKits() as $kit) {
            $ui->addButton($kit->getName(), $kit->getIconForm()->getType(), $kit->getIconForm()->getPath(), $kit->getId());
        }
        $player->sendForm($ui);
    }


    public function UIEditKit(Player $player, Kit $kit): void
    {
        $ui = new SimpleForm(function (Player $player, ?int $data) use ($kit): void {
            if ($data === null) {
                $this->UIEditIndex($player);
                return;
            }
            switch ($data) {
                case 0:
                    $this->UIEditKitGeneralInfo($player, $kit);
                    break;
                case 1:
                    $this->GUIEditOrCreateKitItems($player, $kit, true);
                    break;
                case 2:
                    $this->UIConfirmeRemoveKit($player, $kit);
                    break;
            }
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_general_information()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_items()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_remove()));
        $player->sendForm($ui);
    }

    public function UIEditKitGeneralInfo(Player $player, Kit $kit): void
    {
        $ui = new CustomForm(function (Player $player, ?array $data) use ($kit): void {
            if ($data === null) return;
            $kit->setIconForm($data[1]);
            $kit->setPermission($data[2]);
            $kit->setDelay($data[3]);
            $kit->save();
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_save_kit($kit)));
            $this->UIEditKit($player, $kit);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_name()), $kit->getName(), $kit->getName());
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_image()), $kit->getIconForm()->getPath(), $kit->getIconForm()->getPath());
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_permission()), $kit->getPermission(), $kit->getPermission());
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_delay()), $kit->getDelay(), $kit->getDelay());

        $player->sendForm($ui);
    }

    private function GUIEditOrCreateKitItems(Player $player, Kit $kit, bool $edit = false): void
    {
        $chestMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $chestMenu->setName(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_gui($kit)));
        foreach ($kit->getItems() as $item) {
            $chestMenu->getInventory()->addItem($item);
        }
        $barrier = VanillaBlocks::BARRIER()->asItem()->setCustomName("Illegal slot");
        $barrier->getNamedTag()->setByte("illegal", true);
        for ($i = 36; $i < 54; $i++) {
            $chestMenu->getInventory()->setItem($i, clone $barrier);
        }
        $chestMenu->setListener(function (InvMenuTransaction $transaction) use ($kit): InvMenuTransactionResult {
            if ($transaction->getAction()->getSlot() >= 36 && $transaction->getAction()->getSlot() <= 54) {
                return $transaction->discard();
            }
            return $transaction->continue();
        });
        $chestMenu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($kit, $edit): void {
            $kit->setItems(array_filter($inventory->getContents(), function (Item $item): bool {
                return !$item->isNull() && !($item->getNamedTag()->getByte("illegal", false) || $item->getTypeId() === -BlockTypeIds::BARRIER);
            }));
            $kit->save();
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_save_kit($kit)));
            if ($edit) $this->UIEditKit($player, $kit);

        });
        $chestMenu->send($player);

    }

    private function UIConfirmeRemoveKit(Player $player, Kit $kit): void
    {
        $ui = new ModalForm(function (Player $player, ?bool $data) use ($kit): void {
            if ($data === true) {
                $this->removeKit($kit);
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_delete_kit($kit)));
            }
            $this->UIEditIndex($player);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_editor_form($kit)));
        $ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::content_remove_kit($kit)));
        $ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::accepted_button()));
        $ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::cancel_button()));
        $player->sendForm($ui);
    }

    /**
     * @param Kit $kit
     * @return void
     * @throws \JsonException
     */
    private function removeKit(Kit $kit): void
    {
        $configFile = $kit->getConfig()->getPath();
        if (file_exists($configFile)) {
            unlink($configFile);
        }
        unset($this->kits[$kit->getId()]);
        foreach (LanguageManager::getInstance()->getAllLang() as $language) {
            $config = $language->getConfig();
            if ($config->getNested($kit->getDescriptionPath()) === null) continue;
            $config->removeNested($kit->getDescriptionPath());
            $config->save();
        }
    }
}
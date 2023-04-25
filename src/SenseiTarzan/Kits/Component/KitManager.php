<?php

namespace SenseiTarzan\Kits\Component;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Kits\Class\Kits\Kit;
use SenseiTarzan\Kits\Main;
use SenseiTarzan\Kits\Utils\Format;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
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
     */
    public function load(): void
    {
        foreach (PathScanner::scanDirectoryToConfig(Path::join($this->plugin->getDataFolder(), "Kits"), ['yml']) as $config) {
            $kit = Kit::create($config->get('name'), $config->get("image"), $config->getNested("description.path"), $config->getNested("description.default", null), $config->get("permission"),floatval($config->get("delay", -1)), $config->get("items"));
            $this->kits[$kit->getId()] = $kit;
        }
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
    public function getKit(string $name): ?Kit{
        return $this->kits[Format::nameToId($name)] ?? null;
    }

    public function existKit(string $name): bool
    {
        return isset($this->kits[$name]);
    }

    /**
     * @return Kit[]
     */
    public function getKits(): array{
        return $this->kits;
    }

    public function UIindex(Player $player): void
    {
        $ui = new SimpleForm(function (Player $player, ?string $index) : void{
            if ($index === null) return;
            $kit = $this->getKit($index);
            if ($kit === null) return;
            $this->UIKitRecovery($player, $kit);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_index()));

        foreach ($this->getKits() as $kit){
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

            }else{
                $this->UIindex($player);
            }
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_kit_information($kit->getName())));
        $ui->setContent($kit->getDescription($player));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::accepted_button()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::cancel_button()));
        $player->sendForm($ui);
    }

    public function giveKitToPlayer(Player $player, string $kitName): bool{
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

}
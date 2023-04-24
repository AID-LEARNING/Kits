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
        $this->loadKits();
    }

    public function loadKits(): void
    {
        foreach (PathScanner::scanDirectoryToConfig(Path::join($this->plugin->getDataFolder(), "Kits"), ['yml']) as $config) {
            $this->kits[strtolower($name = $config->get('name'))] = Kit::create($name, $config->get("image"), $config->get("description"), $config->get("permission"),floatval($config->get("delay", -1)), $config->get("items"));
        }
    }

    /**
     * @param string $name
     * @return Kit
     */
    public function getKit(string $name): ?Kit{
        return $this->kits[strtolower($name)] ?? null;
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

        });
        foreach ($this->getKits() as $kit){
            if (!$kit->hasPermission($player)) continue;
            $ui->addButton($kit->getName(), $kit->getIconForm()->getType(), $kit->getIconForm()->getPath(), $kit->getId());
        }
        $player->sendForm($ui);
    }

    public function UIKitRecovery(Player $player, Kit $kit): void
    {
        $ui = new SimpleForm(function (Player $player, ?int $index) use($kit): void{
           if ($index === 1){
               $target = KitsPlayerManager::getInstance()->getPlayer($player);
               if (!$kit->hasPermission($player)){
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
                ->setCustomName("§r§l§e{$kit->getName()}§r§f§l§e Kit")
                ->setLore([
                    "§r§7Click to get the kit",
                    "§r§7You can get it every " . LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::get_format_time($kit->getDelay()))
                ]);
                $chest->getNamedTag()->setString("kit", $kit->getId());

               if (!$player->getInventory()->canAddItem($chest)){
                   $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_free_place()));
                   return;
               }
               if ($hasDelay) {
                   KitsPlayerManager::getInstance()->getPlayer($player)->addWaitingPeriod($kit->getId(), $kit->getDelay());
               }
               $player->getInventory()->addItem($chest);
               $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::success_get_kit($kit->getName())));

           }
        });
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

        return false;
    }

}
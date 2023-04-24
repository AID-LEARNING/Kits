<?php

namespace SenseiTarzan\Kits;

use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\Kits\Class\Save\JSONSave;
use SenseiTarzan\Kits\Class\Save\YAMLSave;
use SenseiTarzan\Kits\Commands\KitCommand;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Listener\PlayerListener;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase
{

    protected function onLoad(): void
    {
        if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
            $this->saveResource("config.yml");
        }
        $typeSave = $this->getConfig()->get("type-save");
        match ($typeSave) {
            "yaml" => DataManager::getInstance()->setDataSystem(new YAMLSave($this->getDataFolder())),
            "json" => DataManager::getInstance()->setDataSystem(new JSONSave($this->getDataFolder()))
        };
        new KitManager($this);
        new LanguageManager($this);
    }

    protected function onEnable(): void
    {
        if (DataManager::getInstance()->getDataSystem() === null) {
            $this->getLogger()->alert("no DataSystem selected");
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        EventLoader::loadEventWithClass($this, PlayerListener::class);
        LanguageManager::getInstance()->loadCommands("kits");

        $this->getServer()->getCommandMap()->register("kits", new KitCommand($this, "kit", "Kits command", ["kits"]));
    }

}
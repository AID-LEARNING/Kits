<?php

namespace SenseiTarzan\Kits;

use CortexPE\Commando\PacketHooker;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\InvMenuTypeRegistry;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\VanillaBlocks;
use pocketmine\plugin\PluginBase;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\Kits\Class\Save\JSONSave;
use SenseiTarzan\Kits\Class\Save\YAMLSave;
use SenseiTarzan\Kits\Commands\KitCommand;
use SenseiTarzan\Kits\Commands\TestCommand;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Listener\PlayerListener;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase
{


    protected function onLoad(): void
    {
        if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
            foreach (PathScanner::scanDirectoryGenerator($search =  Path::join(dirname(__DIR__,3) , "resources")) as $file){
                @$this->saveResource(str_replace($search, "", $file));
            }
        }
        new LanguageManager($this);
        $typeSave = $this->getConfig()->get("type-save");
        match ($typeSave) {
            "yaml" => DataManager::getInstance()->setDataSystem(new YAMLSave($this->getDataFolder())),
            "json" => DataManager::getInstance()->setDataSystem(new JSONSave($this->getDataFolder()))
        };
        new KitManager($this);
    }

    protected function onEnable(): void
    {
        if (DataManager::getInstance()->getDataSystem() === null) {
            $this->getLogger()->alert("no DataSystem selected");
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);

        }
        EventLoader::loadEventWithClass($this, PlayerListener::class);
        LanguageManager::getInstance()->loadCommands("kits");

        $this->getServer()->getCommandMap()->register("kits", new KitCommand($this, "kit", "Kits command", ["kits"]));
    }

}
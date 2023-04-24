<?php

namespace SenseiTarzan\Kits\Class\Save;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\DataBase\Class\IDataSave;
use SenseiTarzan\Kits\Component\KitsPlayerManager;
use SenseiTarzan\Kits\Utils\Convertor;
use SenseiTarzan\Kits\Class\Kits\KitsPlayer;
use SenseiTarzan\Kits\Class\Kits\WaitingPeriod;
use SenseiTarzan\Kits\Class\Role\RolePlayer;
use SenseiTarzan\Kits\Component\Kits;
use SenseiTarzan\Kits\Component\RolePlayerManager;
use Symfony\Component\Filesystem\Path;

class YAMLSave implements IDataSave
{

    private Config $config;

    public function __construct(string $dataFolder)
    {
        $this->config = new Config(Path::join($dataFolder, "data.yml"), Config::YAML);
    }

    public function getName(): string
    {
        return "Yaml System";
    }

    public function loadDataPlayer(Player|string $player): void
    {
        if (!$this->config->exists($name = strtolower($player instanceof Player ? $player->getName() : $player), true)) {
            KitsPlayerManager::getInstance()->loadPlayer($kitsPlayer = KitsPlayer::create($name, []));
            $this->config->set($name, $kitsPlayer->jsonSerialize());
            $this->config->save();
            return;
        }
        $listWaitingPeriod = $this->config->getNested($name . ".listWaitingPeriod", []);
        KitsPlayerManager::getInstance()->loadPlayer(KitsPlayer::create($name, Convertor::jsonToWaitingPeriod($listWaitingPeriod)));
    }


    /**
     * @param string $id
     * @param string $type "addWaitingPeriod" | "removeWaitingPeriod" | "clearWaitingPeriod"
     * @param WaitingPeriod|string|array $data
     * @return void
     * @throws \JsonException
     */
    public function updateOnline(string $id, string $type, mixed $data): void
    {
        if ($type === "addWaitingPeriod") {
            if (!$data instanceof WaitingPeriod) return;
            $type = "listWaitingPeriod." . strtolower($data->getName());
            $data = $data->getPeriod();
        } elseif ($type === "removeWaitingPeriod") {
            $type = "listWaitingPeriod." . strtolower($data);
            $this->config->removeNested($id . ".$type");
            $this->config->save();
            return;
        } else if ($type === "clearWaitingPeriod") {
            $type = "listWaitingPeriod";
            $data = [];
        }
        $this->config->setNested($id . ".$type", $data);
        $this->config->save();
    }


    /**
     * @param string $id
     * @param string $type
     * @param mixed $data
     * @return void
     * @throws JsonException
     */
    public function updateOffline(string $id, string $type, mixed $data): void
    {
        //No need
    }
}
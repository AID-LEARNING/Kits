<?php

namespace SenseiTarzan\Kits\Component;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Kits\Class\Kits\KitsPlayer;

class KitsPlayerManager
{
    use SingletonTrait;

    /**
     * @var KitsPlayer[]
     */
    private array $players = [];

    /**
     * @param KitsPlayer $kitsPlayer
     * @return void
     */
    public function loadPlayer(KitsPlayer $kitsPlayer): void
    {
        $this->players[$kitsPlayer->getId()] = $kitsPlayer;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function unloadPlayer(Player $player): void
    {
        unset($this->players[strtolower($player->getName())]);
    }

    /**
     * @param Player|string $player
     * @return KitsPlayer|null
     */
    public function getPlayer(Player|string $player): ?KitsPlayer
    {
        return $this->players[strtolower($player instanceof Player ? $player->getName() : $player)] ?? null;
    }
}
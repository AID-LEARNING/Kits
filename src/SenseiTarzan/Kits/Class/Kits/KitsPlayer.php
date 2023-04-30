<?php

namespace SenseiTarzan\Kits\Class\Kits;

use pocketmine\player\Player;
use pocketmine\Server;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Utils\Convertor;
use SenseiTarzan\Kits\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

class KitsPlayer implements \JsonSerializable
{

    public function __construct(private string $username, public array $listWaitingPeriod)
    {
    }
    public static function create(string $username, array $listWaitingPeriod): self
    {
        return new self($username, Convertor::jsonToWaitingPeriod($listWaitingPeriod));
    }

    public function getId(): string
    {
        return strtolower($this->getUsername());
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPlayer(): ?Player
    {
        return Server::getInstance()->getPlayerExact($this->getUsername());
    }

    /**
     * @return WaitingPeriod[]
     */
    public function getListWaitingPeriod(): array
    {
        return $this->listWaitingPeriod;
    }

    public function getWaitingPeriod(string $kit): ?WaitingPeriod
    {
        return $this->listWaitingPeriod[$kit] ?? null;
    }

    public function hasWaitingPeriod(string $kit): bool
    {
        return isset($this->listWaitingPeriod[$kit]);
    }

    public function addWaitingPeriod(string $kit, float $second): void
    {
        $kit = KitManager::getInstance()->getKit($kit);
        if ($kit === null) return;
        $this->listWaitingPeriod[$kit->getId()] = new WaitingPeriod($kit->getId(), time() + $second);
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "addWaitingPeriod", $this->listWaitingPeriod[$kit->getId()]);
        $this->getPlayer()?->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($this->getPlayer(), CustomKnownTranslationFactory::add_waiting_period($kit->getName(), $kit->getDelay())));

    }

    public function clearAllWaitingPeriod(): void
    {
        $this->listWaitingPeriod = [];
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "clearWaitingPeriod", null);
    }

    public function removeWaitingPeriod(string $kit): void
    {
        if (!$this->hasWaitingPeriod($kit)) return;
        unset($this->listWaitingPeriod[$kit]);
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "removeWaitingPeriod", strtolower($kit));
    }

    public function canRetrieveKit(string $kit): bool
    {
        return (!$this->hasWaitingPeriod($kit)) || ($this->getWaitingPeriod($kit)?->isCompleted() ?? true);
    }

    public function getListWaitingPeriodToJSON(): array
    {
        $json = [];
        foreach ($this->getListWaitingPeriod() as $waitingPeriod) {
            $json[$waitingPeriod->getName()] = $waitingPeriod->getPeriod();
        }
        return $json;
    }

    public function jsonSerialize(): array
    {
        return $this->getListWaitingPeriodToJSON();
    }
}
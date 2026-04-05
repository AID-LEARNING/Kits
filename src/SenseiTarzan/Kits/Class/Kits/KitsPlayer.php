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

use Generator;
use pocketmine\player\Player;
use SenseiTarzan\Kits\Class\Exception\KitNoHasWaitingPeriodException;
use SenseiTarzan\Kits\Class\Exception\KitNotExistException;
use SenseiTarzan\Kits\Class\Exception\PlayerNoHasWaitingPeriodException;
use SenseiTarzan\Kits\Component\KitManager;
use SenseiTarzan\Kits\Main;
use SenseiTarzan\Kits\Utils\Convertor;
use SOFe\AwaitGenerator\Await;
use WeakReference;
use function mb_strtolower;
use function strtolower;
use function time;

class KitsPlayer implements \JsonSerializable
{

	private string $id;

	/** @var WeakReference<Player> */
	private readonly WeakReference $player;

	public function __construct(Player $player, public array $listWaitingPeriod)
	{
		$this->id = mb_strtolower($player->getName());
		$this->player = WeakReference::create($player);
	}

	public static function create(Player $player, array $listWaitingPeriod) : self
	{
		return new self($player, Convertor::jsonToWaitingPeriod($listWaitingPeriod));
	}

	public function getId() : string
	{
		return strtolower($this->getUsername());
	}

	public function isOnline() : bool
	{
		$player = $this->player->get();
		return $player !== null && $player->isOnline();
	}

	public function getUsername() : string
	{
		$player = $this->player->get();
		if ($player === null) {
			return $this->id;
		}
		return $player->getName();
	}

	public function getPlayer() : ?Player
	{
		return $this->player->get();
	}

	/**
	 * @return WaitingPeriod[]
	 */
	public function getListWaitingPeriod() : array
	{
		return $this->listWaitingPeriod;
	}

	public function getWaitingPeriod(string $kit) : ?WaitingPeriod
	{
		return $this->listWaitingPeriod[$kit] ?? null;
	}

	public function hasWaitingPeriod(string $kit) : bool
	{
		return isset($this->listWaitingPeriod[$kit]);
	}

	public function addWaitingPeriod(string $kit, float $second) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($kit, $second) : void {
			$kit = KitManager::getInstance()->getKit($kit);
			if ($kit === null) {
				$reject(new KitNotExistException("$kit no exist"));
				return;
			}
			Await::f2c(function () use ($kit, $second) : Generator {
				if ($kit->getDelay() === 0.0) {
					return null;
				}
				$waitingPeriod = new WaitingPeriod($kit->getId(), time() + $second);
				yield from Main::getInstance()->getDataManager()->getDataSystem()->updateOnline($this->getId(), "addWaitingPeriod", $waitingPeriod);
				return $waitingPeriod;
			}, function (?WaitingPeriod $waitingPeriod) use ($kit, $resolve) {
				if ($waitingPeriod !== null)
					$this->listWaitingPeriod[$kit->getId()] = $waitingPeriod;
				$resolve();
			}, $reject);
		});
	}

	public function clearAllWaitingPeriod() : Generator
	{
		return Await::promise(function ($resolve, $reject) : void {
			Await::f2c(function () : Generator {
				if (empty($this->listWaitingPeriod)) {
					throw new PlayerNoHasWaitingPeriodException();
				}
				yield from Main::getInstance()->getDataManager()->getDataSystem()->updateOnline($this->getId(), "clearWaitingPeriod", null);
			}, $resolve, $reject);
		});
	}

	public function removeWaitingPeriod(string $kit) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($kit) : void {
			if (!$this->hasWaitingPeriod($kit)) {
				$reject(new PlayerNoHasWaitingPeriodException("$kit no has waiting period"));
				return;
			}
			$kit = KitManager::getInstance()->getKit($kit);
			if ($kit === null) {
				$reject(new KitNotExistException("$kit no exist"));
				return;
			}
			Await::f2c(function () use ($kit) : Generator {
				if ($kit->getDelay() > 0) {
					throw new KitNoHasWaitingPeriodException();
				}
				yield from Main::getInstance()->getDataManager()->getDataSystem()->updateOnline($this->getId(), "removeWaitingPeriod", $kit->getId());
			}, function () use ($kit, $resolve) {
				unset($this->listWaitingPeriod[$kit->getId()]);
				$resolve();
			}, $reject);
		});
	}

	public function canRetrieveKit(string $kit) : bool
	{
		return (!$this->hasWaitingPeriod($kit)) || ($this->getWaitingPeriod($kit)?->isCompleted() ?? true);
	}

	public function getListWaitingPeriodToJSON() : array
	{
		$json = [];
		foreach ($this->getListWaitingPeriod() as $waitingPeriod) {
			$json[$waitingPeriod->getName()] = $waitingPeriod->getPeriod();
		}
		return $json;
	}

	public function jsonSerialize() : array
	{
		return $this->getListWaitingPeriodToJSON();
	}
}

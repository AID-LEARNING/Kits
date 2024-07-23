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

namespace SenseiTarzan\Kits\Utils;

use pocketmine\utils\TextFormat;
use function floor;
use function mb_strtolower;
use function str_replace;
use function time;

class Format
{

	public static function nameToId(string $name) : string
	{
		return mb_strtolower(str_replace([" ", "-", "_"], ["_"], TextFormat::clean($name)), "ASCII");
	}

	public static function remainingTime(float $time) : float{
		return $time - time();
	}

	public static function formatTime(float $time) : array
	{
		return [
			'days' => floor(floor($time) / 86400),
			'hours' => floor($time / 3600) % 24,
			'minutes' => floor($time / 60) % 60,
			'seconds' => $time % 60
		];
	}

}

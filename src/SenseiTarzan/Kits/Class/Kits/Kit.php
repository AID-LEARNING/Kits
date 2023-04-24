<?php

namespace SenseiTarzan\Kits\Class\Kits;

use pocketmine\block\BlockTypeIds;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\Kits\Commands\type\KitListArgument;
use SenseiTarzan\Kits\Utils\Convertor;

class Kit
{

    const DEFAULT_STRING_TAG = "2aa38484-6e72-4e91-943f-838905a7a995";
    /**
     * @var Item[]
     */
    private array $items = [];

    /**
     * @param string $name
     * @param IconForm $iconForm
     * @param string $permission
     * @param float $delay
     * @param array $items
     */
    public function __construct(private string $name, private IconForm $iconForm, private string $permission, private float $delay, array $items)
    {

        KitListArgument::$VALUES[$this->getId()] = $name;
        if (PermissionManager::getInstance()->getPermission($this->permission) === null) {
            PermissionManager::getInstance()->addPermission(new Permission($this->permission, "$name kit permission"));
            ermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)->addChild($this->permission, true);

        }
        $this->items = Convertor::jsonToItems($items);
    }

    public static function create(string $name, string $image, string $permission, float $delay,  array $items): Kit
    {
        return new self($name, IconForm::create($image), $permission, $delay, $items);
    }


    public function getId(): string
    {
        return strtolower($this->getName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return IconForm
     */
    public function getIconForm(): IconForm
    {
        return $this->iconForm;
    }

    /**
     * @return string
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * the delay is in seconds
     * @return float
     */
    public function getDelay(): float
    {
        return $this->delay;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function hasFreePlace(Player $player): bool{
        $inv = $player->getInventory();
        $n = 0;

        $airId = -BlockTypeIds::AIR;
        for ($i = 0; $i < $inv->getSize(); ++$i){
            if ($inv->getItem($i)->getTypeId() !== $airId){
                $n++;
            }
        }


        return $inv->getSize() - $n >= 1;
    }

    public function hasPermission(Player $player): bool{
        return $player->hasPermission($this->getPermission());
    }
}
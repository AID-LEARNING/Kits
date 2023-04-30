<?php

namespace SenseiTarzan\Kits\Class\Kits;

use JsonSerializable;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\Kits\Commands\args\KitListArgument;
use SenseiTarzan\Kits\Utils\Convertor;
use SenseiTarzan\Kits\Utils\Format;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use pocketmine\utils\Config;

class Kit implements JsonSerializable
{

    const DEFAULT_STRING_TAG = "2aa38484-6e72-4e91-943f-838905a7a995";

    private string $id;
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
    public function __construct(private Config $config, private string $name, private IconForm $iconForm, private ?string $descriptionPath, private string $description, private string $permission, private float $delay, array $items)
    {
        $this->id = Format::nameToId($name);
        if ($this->descriptionPath !== null) {
            foreach (LanguageManager::getInstance()->getAllLang() as $language) {
                $config = $language->getConfig();
                if ($config->getNested($this->descriptionPath) !== null) continue;
                $config->setNested($this->descriptionPath, $this->getDescriptionRaw());
                $config->save();
            }
        }
        KitListArgument::$VALUES[$this->getId()] = $name;
        if (PermissionManager::getInstance()->getPermission($this->permission) === null) {
            PermissionManager::getInstance()->addPermission(new Permission($this->permission, "$name kit permission"));
            PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)->addChild($this->permission, true);

        }
        $this->items = Convertor::jsonToItems($items);
    }

    public static function create(Config $config, string $name, string $image, ?string $descriptionPath, string $description, string $permission, float $delay, array $items): Kit
    {
        return new self($config, $name, IconForm::create($image), $descriptionPath, $description, $permission, $delay, $items);
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }


    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param CommandSender|string|null $player
     * @return string
     */
    public function getDescription(CommandSender|string|null $player = null): string
    {
        return $player === null ? $this->getDescriptionRaw() : ($this->descriptionPath !== null ? LanguageManager::getInstance()->getTranslate($player, $this->descriptionPath, [], $this->getDescriptionRaw()) : $this->getDescriptionRaw());
    }

    public function getDescriptionRaw(): string
    {
        return $this->description;
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

    /**
     * @param Player $player
     * @return bool
     */
    public function hasFreePlace(Player $player): bool
    {
        $inv = $player->getInventory();

        foreach ($this->getItems() as $item) {
            if (!$inv->canAddItem($item)) {
                return false;
            }
        }

        return true;
    }

    public function hasPermission(Player $player): bool
    {
        return $player->hasPermission($this->getPermission());
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param float $delay
     */
    public function setDelay(float $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * @param string $iconForm
     */
    public function setIconForm(string $iconForm): void
    {
        $this->iconForm = IconForm::create($iconForm);
    }

    /**
     * @param string $permission
     */
    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }

    /**
     * @param Item[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function save(): void
    {
        $this->getConfig()->setAll($this->jsonSerialize());
        var_dump($this->getConfig()->getAll());
        $this->getConfig()->save();
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->getName(),
            "image" => $this->getIconForm()->getPath(),
            "description" => $this->getDescription(),
            "permission" => $this->getPermission(),
            "delay" => $this->getDelay(),
            "items" => Convertor::itemsToJson($this->getItems())
        ];
    }
}
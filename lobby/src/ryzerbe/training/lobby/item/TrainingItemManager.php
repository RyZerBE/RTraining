<?php

namespace ryzerbe\training\lobby\item;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\core\util\customItem\CustomItemManager;
use ryzerbe\training\lobby\item\type\ChallengeItem;
use ryzerbe\training\lobby\item\type\HubItem;
use ryzerbe\training\lobby\item\type\TeamItem;

class TrainingItemManager {
    use SingletonTrait;

    /** @var CustomItem[] */
    public array $items = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(){
        $items = [
            new ChallengeItem(Item::get(ItemIds::IRON_SWORD)->setCustomName(TextFormat::GOLD."Challenger"), 4),
            new TeamItem(Item::get(ItemIds::SHIELD)->setCustomName(TextFormat::GOLD."Team manager"), 5),
            new HubItem(Item::get(ItemIds::IRON_DOOR)->setCustomName(TextFormat::RED."Go to hub"), 8),
        ];

        foreach($items as $item){
            $this->registerItem($item);
        }
    }

    /**
     * @return CustomItem[]
     */
    public function getItems(): array{
        return $this->items ?? [];
    }

    /**
     * @throws ReflectionException
     */
    public function registerItem(CustomItem $customItem): void{
        $this->items[] = $customItem;
        CustomItemManager::getInstance()->registerCustomItem($customItem);
    }
}
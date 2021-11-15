<?php

namespace ryzerbe\training\lobby\item;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\training\lobby\item\type\ChallengeItem;
use ryzerbe\training\lobby\item\type\HubItem;
use ryzerbe\training\lobby\item\type\TeamItem;
use ryzerbe\training\lobby\util\customItem\CustomItem;
use ryzerbe\training\lobby\util\customItem\CustomItemManager;

class TrainingItemManager {
    use SingletonTrait;

    /** @var array */
    public array $items = [];

    public function __construct(){
        $this->registerItems();
    }

    /**
     * @return TrainingItem[]
     */
    public function getItems(): array{
        return $this->items ?? [];
    }

    /**
     * @param CustomItem $customItem
     */
    public function registerItem(CustomItem $customItem){
        $this->items[] = $customItem;
    }

    /**
     * @throws ReflectionException
     */
    public function registerItems(): void{
        $items = [
            new ChallengeItem(Item::get(ItemIds::IRON_SWORD)->setCustomName(TextFormat::GOLD."Challenger"), 4),
            new TeamItem(Item::get(ItemIds::SHIELD)->setCustomName(TextFormat::GOLD."Team manager"), 5),
            new HubItem(Item::get(ItemIds::IRON_DOOR)->setCustomName(TextFormat::RED."Go to hub"), 8),
        ];

        foreach($items as $item){
            $this->registerItem($item);
            CustomItemManager::getInstance()->registerCustomItem($item);
        }
    }
}
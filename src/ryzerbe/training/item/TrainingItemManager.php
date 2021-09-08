<?php

namespace ryzerbe\training\item;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ryzerbe\training\item\type\ChallengeItem;
use ryzerbe\training\item\type\HubItem;
use ryzerbe\training\item\type\TeamItem;
use ryzerbe\training\util\customItem\CustomItem;
use ryzerbe\training\util\customItem\CustomItemManager;

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
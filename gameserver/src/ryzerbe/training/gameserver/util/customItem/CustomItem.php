<?php

namespace ryzerbe\training\gameserver\util\customItem;

use pocketmine\event\Listener;
use pocketmine\item\Item;
use ryzerbe\core\util\ItemUtils;
use function uniqid;

abstract class CustomItem implements Listener {

    private string $name = "";
    private string $class = "";

    private Item $item;
    private string $uniqueId;

    public function __construct(Item $item){
        $this->uniqueId = uniqid();
        $this->item = ItemUtils::addItemTag($item, $this->getUniqueId(), "custom_item");
    }

    public function getName(): string{
        return $this->name;
    }

    public function setName(string $name): void{
        $this->name = $name;
    }

    public function getClass(): string{
        return $this->class;
    }

    public function setClass(string $class): void{
        $this->class = $class;
    }

    public function getItem(): Item{
        return $this->item;
    }

    public function getUniqueId(): string{
        return $this->uniqueId;
    }

    protected function checkItem(Item $item): bool {
        if(!ItemUtils::hasItemTag($item, "custom_item")) return false;
        $customItemUniqueId = ItemUtils::getItemTag($item, "custom_item");
        $customItem = CustomItemManager::getInstance()->getCustomItem($customItemUniqueId);
        if($customItem === null) return false;
        return $customItemUniqueId === $this->getUniqueId();
    }
}
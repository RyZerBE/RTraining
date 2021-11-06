<?php

namespace ryzerbe\training\util\customItem;

use baubolp\core\util\ItemUtils;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use function uniqid;

abstract class CustomItem implements Listener {

    /** @var string  */
    private string $name = "";
    /** @var string  */
    private string $class = "";

    /** @var Item  */
    private Item $item;
    /** @var string  */
    private string $uniqueId;

    /**
     * SetupItem constructor.
     * @param Item $item
     */
    public function __construct(Item $item){
        $this->uniqueId = uniqid();
        $this->item = ItemUtils::addItemTag($item, $this->getUniqueId(), "custom_item");
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void{
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClass(): string{
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void{
        $this->class = $class;
    }

    /**
     * @return Item
     */
    public function getItem(): Item{
        return $this->item;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string{
        return $this->uniqueId;
    }

    /**
     * @param Item $item
     * @return bool
     */
    protected function checkItem(Item $item): bool {
        if(!ItemUtils::hasItemTag($item, "custom_item")) return false;
        $customItemUniqueId = ItemUtils::getItemTag($item, "custom_item");
        $customItem = CustomItemManager::getInstance()->getCustomItem($customItemUniqueId);
        if($customItem === null) return false;
        return $customItemUniqueId === $this->getUniqueId();
    }
}
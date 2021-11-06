<?php

namespace ryzerbe\training\lobby\util\customItem;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;
use ryzerbe\training\lobby\Training;

class CustomItemManager {
    use SingletonTrait;

    /** @var array  */
    private array $customItems = [];

    /**
     * @return CustomItem[]
     */
    public function getCustomItems(): array{
        return $this->customItems;
    }

    /**
     * @param CustomItem $item
     * @throws ReflectionException
     */
    public function registerCustomItem(CustomItem $item): void {
        $reflection = new ReflectionClass($item::class);
        $item->setName($reflection->getShortName());
        $item->setClass($item::class);
        $this->customItems[$item->getUniqueId()] = $item;
        Server::getInstance()->getPluginManager()->registerEvents($item, Training::getInstance());
    }

    /**
     * @param string $uniqueId
     * @return CustomItem|null
     */
    public function getCustomItem(string $uniqueId): ?CustomItem {
        return $this->customItems[$uniqueId] ?? null;
    }

    /**
     * @param string $name
     * @return CustomItem|null
     */
    public function getCustomItemByName(string $name): ?CustomItem {
        foreach($this->getCustomItems() as $customItem) {
            if($customItem->getName() === $name) return $customItem;
        }
        return null;
    }

    /**
     * @param string $class
     * @return CustomItem|null
     */
    public function getCustomItemByClass(string $class): ?CustomItem {
        foreach($this->getCustomItems() as $customItem) {
            if($customItem->getClass() === $class) return $customItem;
        }
        return null;
    }

    /**
     * @param CustomItem[] $customItems
     */
    public function registerAll(array $customItems): void{
        foreach($customItems as $item)
            $this->registerCustomItem($item);
    }
}
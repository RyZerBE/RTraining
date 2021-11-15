<?php

namespace ryzerbe\training\gameserver\util\customItem;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;
use ryzerbe\training\gameserver\Training;

class CustomItemManager {
    use SingletonTrait;

    /** @var CustomItem[]  */
    private array $customItems = [];

    /**
     * @return CustomItem[]
     */
    public function getCustomItems(): array{
        return $this->customItems;
    }

    /**
     * @throws ReflectionException
     */
    public function registerCustomItem(CustomItem $item): void {
        $reflection = new ReflectionClass($item::class);
        $item->setName($reflection->getShortName());
        $item->setClass($item::class);
        $this->customItems[$item->getUniqueId()] = $item;
        Server::getInstance()->getPluginManager()->registerEvents($item, Training::getInstance());
    }

    public function getCustomItem(string $uniqueId): ?CustomItem {
        return $this->customItems[$uniqueId] ?? null;
    }

    public function getCustomItemByName(string $name): ?CustomItem {
        foreach($this->getCustomItems() as $customItem) {
            if($customItem->getName() === $name) return $customItem;
        }
        return null;
    }

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
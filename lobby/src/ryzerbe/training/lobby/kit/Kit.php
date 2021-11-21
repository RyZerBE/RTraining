<?php

namespace ryzerbe\training\lobby\kit;

use pocketmine\item\Item;

class Kit {
    /** @var string  */
    private string $name;
    /** @var array  */
    private array $items, $armor;

    /**
     * Kit constructor.
     * @param string $name
     * @param array $items
     * @param array $armor
     */
    public function __construct(string $name, array $items, array $armor){
        $this->name = $name;
        $this->items = $items;
        $this->armor = $armor;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return Item[]
     */
    public function getArmor(): array{
        return $this->armor;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array{
        return $this->items;
    }
}
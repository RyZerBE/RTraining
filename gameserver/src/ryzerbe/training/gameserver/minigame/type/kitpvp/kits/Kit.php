<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp\kits;

class Kit {
    private string $name;
    private array $items, $armor;

    public function __construct(string $name, array $items, array $armor){
        $this->name = $name;
        $this->armor = $armor;
        foreach($items as $key => $item) {
            $this->items[$item->getVanillaName()."_".$key] = $item;
        }
    }

    public function getName(): string{
        return $this->name;
    }

    public function getArmor(): array{
        return $this->armor;
    }

    public function getItems(): array{
        return $this->items;
    }
}
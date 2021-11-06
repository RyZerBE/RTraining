<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp\kits;

use pocketmine\Player;

class Kit {
    private string $name;
    private array $items, $armor;

    public function __construct(string $name, array $items, array $armor){
        $this->name = $name;
        $this->items = $items;
        $this->armor = $armor;
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

    public function givePlayer(Player $player){
        $player->getArmorInventory()->setContents($this->getArmor());
        $player->getInventory()->setContents($this->getItems());
    }
}
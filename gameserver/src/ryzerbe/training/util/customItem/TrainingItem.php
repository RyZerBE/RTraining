<?php

namespace ryzerbe\training\util\customItem;

use pocketmine\item\Item;
use pocketmine\Player;

class TrainingItem extends CustomItem {
    private int $slot;

    public function __construct(Item $item, int $slot){
        $this->slot = $slot;
        parent::__construct($item);
    }

    public function getSlot(): int{
        return $this->slot;
    }

    public function giveItem(Player $player, int|bool|null $slot = null): void{
        if($slot === false) {
            $player->getInventory()->addItem($this->getItem());
        } else {
            $player->getInventory()->setItem(($slot ?? $this->getSlot()), $this->getItem());
        }
        $player->resetItemCooldown($this->getItem(), 10);
    }
}
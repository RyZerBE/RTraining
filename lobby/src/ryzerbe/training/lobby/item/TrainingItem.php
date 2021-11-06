<?php

namespace ryzerbe\training\lobby\item;

use pocketmine\item\Item;
use pocketmine\Player;
use ryzerbe\training\lobby\util\customItem\CustomItem;

class TrainingItem extends CustomItem {
    /** @var int  */
    private int $slot;

    /**
     * BedwarsItem constructor.
     * @param Item $item
     * @param int $slot
     */
    public function __construct(Item $item, int $slot){
        $this->slot = $slot;
        parent::__construct($item);
    }

    /**
     * @return int
     */
    public function getSlot(): int{
        return $this->slot;
    }

    /**
     * @param Player $player
     * @param int|bool|null $slot
     */
    public function giveItem(Player $player, int|bool|null $slot = null): void{
        if($slot === false) {
            $player->getInventory()->addItem($this->getItem());
            return;
        }
        $player->getInventory()->setItem(($slot ?? $this->getSlot()), $this->getItem());
    }
}
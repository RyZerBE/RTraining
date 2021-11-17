<?php

namespace ryzerbe\training\gameserver\minigame\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;

class MinigameHubItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 60);

        $player->getServer()->dispatchCommand($player, "leave");
    }

    public function getSlot(): ?int{
        return 5;
    }
}
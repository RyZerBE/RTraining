<?php

namespace ryzerbe\training\gameserver\minigame\type\bridger\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\bridger\form\BridgerMinigameConfigurationForm;

class BridgerMinigameConfigurationItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        BridgerMinigameConfigurationForm::open($player);
    }

    public function getSlot(): ?int{
        return 8;
    }
}
<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\form\AimTrainerConfigurationForm;

class AimTrainerConfigurationItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);
        AimTrainerConfigurationForm::open($player);
    }

    public function getSlot(): ?int{
        return 8;
    }
}
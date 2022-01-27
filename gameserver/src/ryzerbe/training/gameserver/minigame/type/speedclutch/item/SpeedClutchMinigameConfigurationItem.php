<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\speedclutch\form\SpeedClutchMinigameConfigurationForm;

class SpeedClutchMinigameConfigurationItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, ($player->isOp() ? 10 : 100));
       SpeedClutchMinigameConfigurationForm::open($player);
    }
}
<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\form\HitBlockClutchMinigameConfigurationForm;

class HitBlockClutchMinigameConfigurationItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 100);
        HitBlockClutchMinigameConfigurationForm::open($player);
    }
}
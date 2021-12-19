<?php

namespace ryzerbe\training\lobby\item\type;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\lobby\form\type\MatchSpectateForm;

class SpectateItem extends CustomItem {

    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        MatchSpectateForm::onOpen($player);
    }
}
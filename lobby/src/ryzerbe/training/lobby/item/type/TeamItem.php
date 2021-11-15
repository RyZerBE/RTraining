<?php

namespace ryzerbe\training\lobby\item\type;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\training\lobby\form\type\TeamInviteProgressForm;
use ryzerbe\training\lobby\form\type\TeamSettingForm;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class TeamItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;

        if($trainingPlayer->getTeam() !== null) {
            TeamSettingForm::open($player);
        }else {
            TeamInviteProgressForm::open($player);
        }
    }
}
<?php

namespace ryzerbe\training\lobby\item\type;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\lobby\form\type\TeamInviteProgressForm;
use ryzerbe\training\lobby\form\type\TeamSettingForm;
use ryzerbe\training\lobby\item\TrainingItem;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class TeamItem extends TrainingItem {
    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
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
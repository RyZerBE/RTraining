<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\item;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\form\AimTrainerConfigurationForm;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;

class AimTrainerConfigurationItem extends TrainingItem {

    public function getSlot(): int{
        return 8;
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        AimTrainerConfigurationForm::open($player);
    }
}
<?php

namespace ryzerbe\training\gameserver\minigame\type\bridger\item;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\gameserver\minigame\type\bridger\form\BridgerMinigameConfigurationForm;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;

class BridgerMinigameConfigurationItem extends TrainingItem {

    public function getSlot(): int{
        return 8;
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        BridgerMinigameConfigurationForm::open($player);
    }
}
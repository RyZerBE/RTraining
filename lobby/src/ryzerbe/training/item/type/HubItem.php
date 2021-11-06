<?php

namespace ryzerbe\training\item\type;

use BauboLP\Cloud\CloudBridge;
use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\item\TrainingItem;

class HubItem extends TrainingItem {

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "hub");
    }
}
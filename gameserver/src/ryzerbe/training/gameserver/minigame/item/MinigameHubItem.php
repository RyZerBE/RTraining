<?php

namespace ryzerbe\training\gameserver\minigame\item;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;

class MinigameHubItem extends TrainingItem {

    public function getSlot(): int{
        return 5;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 60);

        $event->setCancelled();
        $player->getServer()->dispatchCommand($player, "leave");
    }
}
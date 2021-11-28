<?php

namespace ryzerbe\training\gameserver\listener\block;

use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class BlockUpdateListener implements Listener {
    public function onBlockUpdate(BlockUpdateEvent $event): void {
        $minigame = MinigameManager::getMinigameByLevel($event->getBlock()->getLevel());
        if($minigame === null) {
            $event->setCancelled();
        }
    }
}
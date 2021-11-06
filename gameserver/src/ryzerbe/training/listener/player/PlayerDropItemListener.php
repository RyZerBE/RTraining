<?php

namespace ryzerbe\training\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\Player;
use ryzerbe\training\minigame\MinigameManager;

class PlayerDropItemListener implements Listener {

    /**
     * @param PlayerDropItemEvent $event
     * @priority LOW
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            $event->setCancelled();//TODO: Cancel event?
            return;
        }
        $event->setCancelled(!$minigame->getSettings()->itemDrop);
    }
}
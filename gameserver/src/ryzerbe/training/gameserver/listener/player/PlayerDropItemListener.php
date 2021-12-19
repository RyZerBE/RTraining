<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class PlayerDropItemListener implements Listener {

    /**
     * @param PlayerDropItemEvent $event
     * @priority LOW
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        if($player->isSpectator()) {
            $event->setCancelled();
            return;
        }
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            $event->setCancelled();
            return;
        }
        $event->setCancelled(!$minigame->getSettings()->itemDrop);
    }
}
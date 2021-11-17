<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class PlayerExhaustListener implements Listener {

    /**
     * @param PlayerExhaustEvent $event
     * @priority LOW
     */
    public function onPlayerExhaust(PlayerExhaustEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            $event->setCancelled();
        } else {
            $event->setCancelled(!$minigame->getSettings()->hunger);
        }
        if($event->isCancelled()) $player->setFood(20.0);
    }
}
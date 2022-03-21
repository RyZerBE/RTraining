<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $event
     * @priority LOW
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            if($player->isCreative()) return;
            $event->setCancelled();
            return;
        }
        if($player->isSpectator()){
            $event->setCancelled();
            return;
        }
        $action = $event->getAction();
        $settings = $minigame->getSettings();
        $cancel = !$settings->canInteract;
        switch($action) {
            case PlayerInteractEvent::RIGHT_CLICK_BLOCK: {
                if($settings->canPlace) break;
            }
            default: {
                $event->setCancelled($cancel);
            }
        }
    }
}
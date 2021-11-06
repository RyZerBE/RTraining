<?php

namespace ryzerbe\training\listener\inventory;

use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use ryzerbe\training\minigame\MinigameManager;

class InventoryPickupItemListener implements Listener {

    /**
     * @param InventoryPickupItemEvent $event
     * @priority LOW
     */
    public function onInventoryPickupItem(InventoryPickupItemEvent $event): void {
        foreach($event->getInventory()->getViewers() as $player) {
            $minigame = MinigameManager::getMinigameByPlayer($player);
            if($minigame === null) {
                $event->setCancelled();//TODO: Cancel event?
                break;
            }
            $event->setCancelled(!$minigame->getSettings()->itemPickup);
            break;
        }
    }
}
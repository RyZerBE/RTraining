<?php

namespace ryzerbe\training\gameserver\listener\inventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class InventoryTransactionListener implements Listener {

    /**
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void {
        $player = $event->getTransaction()->getSource();
        if($player->isCreative(true)) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            if($player->isCreative()) return;
            $event->setCancelled();
            return;
        }
        if(!$minigame->getSettings()->inventoryTransactions) {
            foreach($event->getTransaction()->getActions() as $action) {
                if($action instanceof SlotChangeAction) {
                    $event->setCancelled();
                }
            }
        }
    }
}
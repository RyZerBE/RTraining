<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\training\lobby\inventory\InventorySortManager;
use ryzerbe\training\lobby\item\TrainingItemManager;

class PlayerSneakListener implements Listener {
    public function onPlayerSneak(PlayerToggleSneakEvent $event): void{
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();

        $session = InventorySortManager::getInstance()->getSession($player);
        if($session !== null) {
            InventorySortManager::getInstance()->saveSession($session);
            InventorySortManager::getInstance()->removeSession($session);
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
            $player->getInventory()->clearAll();
            foreach(TrainingItemManager::getInstance()->getItems() as $trainingItem) {
                $trainingItem->giveToPlayer($player);
            }
            $player->getInventory()->setHeldItemIndex(4);
            $player->setImmobile(false);
            $player->removeAllEffects();
        }
    }
}
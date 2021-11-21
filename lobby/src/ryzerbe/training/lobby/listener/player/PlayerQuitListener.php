<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\training\lobby\gamezone\GameZoneManager;
use ryzerbe\training\lobby\inventory\InventorySortManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\queue\QueueManager;

class PlayerQuitListener implements Listener {

    public function onQuit(PlayerQuitEvent $event){
        $event->setQuitMessage("");
        $player = $event->getPlayer();
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;

        foreach(QueueManager::getInstance()->getQueues() as $queue) {
            $queue->removePlayer($player);
        }

        $trainingPlayer->getPlayerSettings()->saveToDatabase();
        $trainingPlayer->getTeam()?->leave($trainingPlayer);
        GameZoneManager::getInstance()->removePlayer($player);
        TrainingPlayerManager::removePlayer($player);

        $session = InventorySortManager::getInstance()->getSession($player);
        if($session !== null) {
            InventorySortManager::getInstance()->saveSession($session);
            InventorySortManager::getInstance()->removeSession($session);
        }
    }
}
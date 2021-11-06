<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\training\lobby\kit\KitManager;
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
        TrainingPlayerManager::removePlayer($player);
        unset(KitManager::getInstance()->sort[$player->getName()]);
    }
}
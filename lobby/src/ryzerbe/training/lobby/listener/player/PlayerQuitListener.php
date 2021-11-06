<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\training\lobby\kit\KitManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class PlayerQuitListener implements Listener {

    public function onQuit(PlayerQuitEvent $event){
        $event->setQuitMessage("");
        $player = $event->getPlayer();
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;

        $trainingPlayer->getPlayerSettings()->saveToDatabase();
        $trainingPlayer->getTeam()?->leave($trainingPlayer);
        TrainingPlayerManager::removePlayer($player);
        unset(KitManager::getInstance()->sort[$player->getName()]);
    }
}
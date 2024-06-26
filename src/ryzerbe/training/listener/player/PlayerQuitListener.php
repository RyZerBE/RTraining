<?php

namespace ryzerbe\training\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\training\kit\KitManager;
use ryzerbe\training\player\TrainingPlayerManager;

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
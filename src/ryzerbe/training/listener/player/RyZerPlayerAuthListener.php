<?php

namespace ryzerbe\training\listener\player;

use baubolp\core\listener\own\RyZerPlayerAuthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;
use ryzerbe\training\item\TrainingItemManager;
use ryzerbe\training\player\TrainingPlayer;
use ryzerbe\training\player\TrainingPlayerManager;

class RyZerPlayerAuthListener implements Listener {

    /**
     * @param RyZerPlayerAuthEvent $event
     */
    public function onAuth(RyZerPlayerAuthEvent $event){
        $player = $event->getRyZerPlayer()->getPlayer();
        $trainingPlayer = new TrainingPlayer($player);
        TrainingPlayerManager::addPlayer($trainingPlayer);

        foreach(TrainingItemManager::getInstance()->getItems() as $trainingItem) {
            $trainingItem->giveItem($player);
        }
        $player->getInventory()->setHeldItemIndex(4);
        $player->setImmobile(false);
        $player->setGamemode(0);
    }

    public function join(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage("");
        $player->getInventory()->clearAll();
        $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0, 1));
        $player->setImmobile(true);
    }
}
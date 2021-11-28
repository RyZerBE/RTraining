<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Server;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\training\lobby\item\TrainingItemManager;
use ryzerbe\training\lobby\player\TrainingPlayer;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\util\LevelSettings;

class RyZerPlayerAuthListener implements Listener {

    /**
     * @param RyZerPlayerAuthEvent $event
     */
    public function onAuth(RyZerPlayerAuthEvent $event){
        /** @var PMMPPlayer $player */
        $player = $event->getRyZerPlayer()->getPlayer();
        $trainingPlayer = new TrainingPlayer($player);
        TrainingPlayerManager::addPlayer($trainingPlayer);

        foreach(TrainingItemManager::getInstance()->getItems() as $trainingItem) {
            $trainingItem->giveToPlayer($player);
        }
        $player->getInventory()->setHeldItemIndex(4);
        $player->setImmobile(false);
        $player->setGamemode(0);
    }

    public function join(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage("");
        $player->getInventory()->clearAll();
        $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0.5, 1, 0.5), 180, 0);
        $player->setImmobile(true);

        if(LevelSettings::SNOW) {
            $pk = new LevelEventPacket();
            $pk->evid = 3001;
            $pk->data = 10000;
            $player->dataPacket($pk);
        }
    }
}
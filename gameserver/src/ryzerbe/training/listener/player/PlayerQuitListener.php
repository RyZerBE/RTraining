<?php

namespace ryzerbe\training\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\training\session\SessionManager;
use function count;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $event->setQuitMessage("");

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session !== null) {
            if(count($session->getTeams()) > 0) {
                $ev = new PlayerDeathEvent($player, []);
                $ev->call();
                $session->removePlayer($player->getName());
                return;
            }
            $session->getMinigame()->getSessionManager()->removeSession($session);
            SessionManager::getInstance()->removeSession($session);
        }
    }
}
<?php

namespace ryzerbe\training\gameserver\listener\player;

use baubolp\core\provider\LanguageProvider;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use ryzerbe\training\gameserver\session\SessionManager;

class PlayerDeathListener implements Listener {
    /**
     * @priority LOW
     */
    public function onDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        $event->setKeepInventory(true);
        $killer = $player->getLastAttackedEntity();

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;

        if(!$killer instanceof Player){
            foreach($session->getOnlinePlayers() as $levelPlayer){
                $levelPlayer->sendMessage($session->getMinigame()->getSettings()->PREFIX.LanguageProvider::getMessageContainer('player-fell-in-void', $levelPlayer->getName(), ['#playername' => $player->getDisplayName()]));
            }
        }else{
            foreach($session->getOnlinePlayers() as $levelPlayer){
                $levelPlayer->sendMessage($session->getMinigame()->getSettings()->PREFIX.LanguageProvider::getMessageContainer('player-killed-by-player', $levelPlayer->getName(), ["#killername" => $killer->getDisplayName(), '#playername' => $player->getDisplayName()]));
            }

            $player->setLastAttackedEntity(null);
            $killer->setLastAttackedEntity(null);
        }
    }
}
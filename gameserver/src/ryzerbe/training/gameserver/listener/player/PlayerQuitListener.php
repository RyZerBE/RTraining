<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\TextFormat;
use ryzerbe\bedwars\Loader;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\session\TournamentSession;
use ryzerbe\training\gameserver\Training;
use function count;

class PlayerQuitListener implements Listener {
    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        $event->setQuitMessage("");

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session !== null) {
            if($session instanceof TournamentSession) {
                $session->removePlayer($player->getName());

                if($session->isRunning()) {
                    foreach($session->getOnlinePlayers() as $onlinePlayer) {
                        $onlinePlayer->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("player-eliminated", $onlinePlayer, ["#player" => $player->getName()]));
                    }
                } else {
                    foreach($session->getOnlinePlayers() as $onlinePlayer) {
                        $onlinePlayer->sendMessage(Loader::$PREFIX."[".TextFormat::RED."-".TextFormat::WHITE."] ".TextFormat::RESET.$player->getName());
                    }
                }

                if(count($session->getOnlinePlayers()) <= 0) {
                    SessionManager::getInstance()->removeSession($session);
                }
            } else {
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
}
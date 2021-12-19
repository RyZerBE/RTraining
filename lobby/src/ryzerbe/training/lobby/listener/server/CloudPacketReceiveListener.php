<?php

namespace ryzerbe\training\lobby\listener\server;

use BauboLP\Cloud\Events\CloudPacketReceiveEvent;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\event\Listener;
use ryzerbe\training\lobby\spectate\MatchSpectate;
use ryzerbe\training\lobby\spectate\MatchSpectateManager;
use function json_decode;

class CloudPacketReceiveListener implements Listener {

    public function onReceive(CloudPacketReceiveEvent $event){
        $packet = $event->getCloudPacket();
        if($packet instanceof MatchPacket) {
            $matchArray = $packet->getValue("matches") ?? null;
            if($matchArray === null) return;
            MatchSpectateManager::getInstance()->setMatches([]);
            $matches = (array) json_decode($matchArray);
            foreach($matches as $matchId => $matchData) {
                $matchData = (array) $matchData;
                MatchSpectateManager::getInstance()->addMatchSpectate(new MatchSpectate(
                    $matchId,
                    $matchData["teams"] ?? [],
                    $matchData["players"] ?? [],
                    $matchData["minigame"]
                ));
            }
        }
    }
}
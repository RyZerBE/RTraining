<?php

namespace ryzerbe\training\listener\cloudbridge;

use BauboLP\Cloud\Events\CloudPacketReceiveEvent;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\event\Listener;
use ryzerbe\training\game\match\MatchQueue;
use ryzerbe\training\game\match\MatchRequest;
use ryzerbe\training\minigame\MinigameManager;
use ryzerbe\training\minigame\type\kitpvp\KitPvPMinigame;
use ryzerbe\training\util\Logger;
use function count;
use function json_decode;

class CloudPacketReceiveListener implements Listener{

    /**
     * @param CloudPacketReceiveEvent $event
     */
    public function onPacketReceive(CloudPacketReceiveEvent $event): void {
        $packet = $event->getCloudPacket();

        if($packet instanceof MatchPacket) {
            $minigameName = $packet->getValue("minigame") ?? "Unknown";
            $minigame = MinigameManager::getMinigame($minigameName);
            if($minigame === null) {
                Logger::error("Received MatchPacket with unknown minigame");
                return;
            }

            $players = $packet->getValue("players");
            if($players !== null) {
                $players = json_decode($players, true);
                if($minigame->getSettings()->maxPlayers < count($players)) {
                    Logger::error("Player count does not match with minigame max players");
                    return;
                }
                $request = new MatchRequest($players, $minigameName, (bool)$packet->getValue("elo") ?? false);

                $teams = $packet->getValue("teams");
                if($teams !== null) {
                    $teams = json_decode($teams, true);
                    $request->setTeams($teams);
                }

                if($minigame instanceof KitPvPMinigame) {
                    $kitName = $packet->getValue("kitName") ?? null;
                    if($kitName !== null) {
                        $request->addExtraData("kitName", $kitName);
                    }
                }

                MatchQueue::addQueue($request);
            }else {
                Logger::error("Unknown players value given");
            }
        }
    }
}
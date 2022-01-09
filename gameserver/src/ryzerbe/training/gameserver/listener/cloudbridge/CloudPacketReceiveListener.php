<?php

namespace ryzerbe\training\gameserver\listener\cloudbridge;

use BauboLP\Cloud\Events\CloudPacketReceiveEvent;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\event\Listener;
use ryzerbe\training\gameserver\game\match\MatchQueue;
use ryzerbe\training\gameserver\game\match\MatchRequest;
use ryzerbe\training\gameserver\game\spectate\SpectateQueue;
use ryzerbe\training\gameserver\game\spectate\SpectateRequest;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\util\Logger;
use function boolval;
use function count;
use function in_array;
use function intval;
use function json_decode;

class CloudPacketReceiveListener implements Listener{
    public function onPacketReceive(CloudPacketReceiveEvent $event): void {
        $packet = $event->getCloudPacket();

        if($packet instanceof MatchPacket) {
            $players = $packet->getValue("players");
            if($players !== null) {
                $spectate = $packet->getValue("spectate");
                if($spectate !== null) {
                    $players = (array) json_decode($players);
                    SpectateQueue::addRequest(new SpectateRequest($players, $spectate));
                    return;
                }

                $minigameName = $packet->getValue("minigame") ?? "Unknown";
                $minigame = MinigameManager::getMinigame($minigameName);
                if($minigame === null) {
                    Logger::error("Received MatchPacket with unknown minigame '".$minigameName."'");
                    return;
                }

                $players = json_decode($players, true);
                if($minigame->getSettings()->maxPlayers < count($players)) {
                    Logger::error("Player count does not match with minigame max players");
                    return;
                }
                $request = new MatchRequest($players, $minigameName, boolval(intval($packet->getValue("elo") ?? false)));

                $teams = $packet->getValue("teams");
                if($teams !== null) {
                    $teams = json_decode($teams, true);
                    $request->setTeams($teams);
                }

                foreach($packet->data as $key => $value) {
                    if(in_array($key, ["minigame", "players", "teams"])) continue;
                    $request->addExtraData($key, $value);
                }
                MatchQueue::addQueue($request);
            }else {
                Logger::error("Unknown players value given");
            }
        }
    }
}
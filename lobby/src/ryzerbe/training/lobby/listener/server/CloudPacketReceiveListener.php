<?php

namespace BauboLP\Cloud\listener\server;

use BauboLP\Cloud\Events\CloudPacketReceiveEvent;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\event\Listener;

class CloudPacketReceiveListener implements Listener {

    public function onReceive(CloudPacketReceiveEvent $event){
        $packet = $event->getCloudPacket();
        if($packet instanceof MatchPacket) {
            $matchArray = $packet->getValue("matches") ?? null;
            if($matchArray === null) return;
            //todo: cache matches and display it in form!
        }
    }
}
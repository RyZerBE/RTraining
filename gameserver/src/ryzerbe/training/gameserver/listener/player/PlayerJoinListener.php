<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\training\gameserver\util\WaitingQueue;

class PlayerJoinListener implements Listener {

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage("");
        $player->setImmobile();
        $player->setGamemode(2);
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 20 * 30, 3, false));
    }

    public function onAuth(RyZerPlayerAuthEvent $event){
        WaitingQueue::addPlayer($event->getRyZerPlayer()->getPlayer());
    }
}
<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\training\gameserver\util\WaitingQueue;

class PlayerJoinListener implements Listener {
    public function onJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        $event->setJoinMessage("");
        $player->setImmobile();
        $player->setGamemode(2);
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 20 * 30, 3, false));
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
    }

    public function onAuth(RyZerPlayerAuthEvent $event): void{
        WaitingQueue::addPlayer($event->getRyZerPlayer()->getPlayer());
    }
}
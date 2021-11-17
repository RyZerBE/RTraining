<?php

namespace ryzerbe\training\lobby\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

class EntityDamageListener implements Listener {


    public function onDamage(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent) return;

        $event->setCancelled();
        if($event->getCause() === EntityDamageEvent::CAUSE_VOID || $event->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION) {
            $event->getEntity()->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0.5, 1, 0.5), 180, 0);
        }
    }
}
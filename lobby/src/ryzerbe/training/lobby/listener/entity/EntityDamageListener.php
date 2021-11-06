<?php

namespace ryzerbe\training\lobby\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;

class EntityDamageListener implements Listener {


    public function onDamage(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent) return;

        $event->setCancelled();
    }
}
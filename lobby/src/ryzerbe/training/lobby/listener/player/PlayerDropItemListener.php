<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;

class PlayerDropItemListener implements Listener {

    public function onDrop(PlayerDropItemEvent $event){
        $event->setCancelled();
    }
}
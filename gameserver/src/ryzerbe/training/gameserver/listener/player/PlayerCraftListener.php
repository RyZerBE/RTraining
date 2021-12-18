<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;

class PlayerCraftListener implements Listener {

    public function craft(CraftItemEvent $event){
        $event->setCancelled();
    }
}
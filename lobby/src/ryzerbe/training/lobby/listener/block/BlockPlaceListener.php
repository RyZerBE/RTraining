<?php

namespace ryzerbe\training\lobby\listener\block;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

class BlockPlaceListener implements Listener {
    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event){
        if($event->getPlayer()->isCreative(true)) return;
        $event->setCancelled();
    }
}
<?php

namespace ryzerbe\training\lobby\listener\block;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event){
        if($event->getPlayer()->isCreative(true)) return;
        $event->setCancelled();
    }
}
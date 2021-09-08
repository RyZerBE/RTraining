<?php

namespace ryzerbe\training\listener\block;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event){
        $event->setCancelled();
    }
}
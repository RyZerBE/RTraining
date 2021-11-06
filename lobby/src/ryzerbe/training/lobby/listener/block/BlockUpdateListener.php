<?php

namespace ryzerbe\training\lobby\listener\block;

use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;

class BlockUpdateListener implements Listener {
    public function onBlockUpdate(BlockUpdateEvent $event): void {
        $event->setCancelled();
    }
}
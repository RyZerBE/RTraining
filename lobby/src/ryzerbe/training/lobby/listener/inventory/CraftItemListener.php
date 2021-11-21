<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\inventory;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;

class CraftItemListener implements Listener {
    public function onItemCraft(CraftItemEvent $event): void {
        $event->setCancelled();
    }
}
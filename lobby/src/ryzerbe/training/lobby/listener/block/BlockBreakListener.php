<?php

namespace ryzerbe\training\lobby\listener\block;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use ryzerbe\training\lobby\gamezone\GameZoneManager;

class BlockBreakListener implements Listener {
    public function onBreak(BlockBreakEvent $event): void{
        $player = $event->getPlayer();
        if($player->isCreative(true)) return;

        /** @var GameZoneManager $gameZone */
        $gameZone = GameZoneManager::getInstance();
        $block = $event->getBlock();
        if($gameZone->isPlayer($player)) {
            if(!$gameZone->isBlock($block)) {
                $event->setCancelled();
                return;
            }
            $event->setDrops([]);
            $gameZone->removeBlock($block);
            return;
        }
        $event->setCancelled();
    }
}